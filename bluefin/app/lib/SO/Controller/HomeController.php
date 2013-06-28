<?php

namespace SO\Controller;

use Bluefin\App;
use SO\Business\AuthBusiness;
use SO\Business\WBTBusiness;
use SO\Business\SocialBusiness;
use SO\Business\SinaWeiboBusiness;
use WBT\Model\Weibotui\WeiboType;

class HomeController extends SOControllerBase
{
    public function indexAction()
    {
        if (AuthBusiness::isLoggedIn())
        {
            $this->_gateway->redirect(_P('home/dashboard'));
        }

        $this->_view->set('loginUrl', WBTBusiness::getClient()->getAuthorizeURL(_U('home/dashboard')));
    }

    public function dashboardAction()
    {
        $loginProfile = $this->_requireLoginProfile();

        if ($loginProfile['weibotui'])
        {//微博推账号
            $snaProfiles = SocialBusiness::getProfilesOfUserSocialAccounts($loginProfile['user_id']);
        }
        else
        {
            $snaProfiles = [SocialBusiness::getSocialAccountProfile($loginProfile['sna_id'])];
        }

        $this->_view->appendOption('filters', [
            'follower_class' => "\\Common\\Helper\\ViewVarMapper::followerToStyle",
            'following_class' => "\\Common\\Helper\\ViewVarMapper::followingToStyle",
            'post_class' => "\\Common\\Helper\\ViewVarMapper::postToStyle",
        ]);

        $this->_view->set('snaProfiles', $snaProfiles);
        $this->_view->set('snAuth', AuthBusiness::getAllSupportedSNAuth());
    }

    public function idAction()
    {
        $loginProfile = $this->_requireLoginProfile();

        $homeToken = $this->_request->getRouteParam('homeToken');
        if (!isset($homeToken) || strlen($homeToken) < 2)
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $typeFlag = $homeToken[0];
        $typeMapper = [
            'w' => WeiboType::WEIBO,
            't' => WeiboType::QQ_WEIBO
        ];

        if (!array_key_exists($typeFlag, $typeMapper))
        {
            throw new \Bluefin\Exception\InvalidRequestException();
        }

        $type = $typeMapper[$typeFlag];
        $id = substr($homeToken, 1);
        $accessToken = null;

        if ($loginProfile['weibotui'])
        {//微博推账号
            $snaProfiles = SocialBusiness::getProfilesOfUserSocialAccounts($loginProfile['user_id']);
            $this->_view->set('snaProfiles', $snaProfiles);

            foreach ($snaProfiles as $snaProfile)
            {
                if ($snaProfile['uid'] == $id && $snaProfile['type'] == $type)
                {
                    $accessToken = $snaProfile['access_token'];
                }
            }

            if (!isset($accessToken))
            {
                throw new \Bluefin\Exception\UnauthorizedException();
            }
        }
        else
        {
            $snaProfile = SocialBusiness::getSocialAccountProfile($loginProfile['sna_id']);
            if ($snaProfile['uid'] != $id || $snaProfile['type'] != $type)
            {
                throw new \Bluefin\Exception\UnauthorizedException();
            }

            $accessToken = $snaProfile['access_token'];
        }

        $userTimeline = null;
        $showingProfile = null;

        switch ($type)
        {
            case WeiboType::WEIBO:
                $showingProfile = SinaWeiboBusiness::getProfile($accessToken, $id);
                $userTimeline = SinaWeiboBusiness::getUserTimeline($accessToken, $id);
                $this->changeView('id_weibo');
                break;

            case WeiboType::QQ_WEIBO:
                break;
        }

        $this->_view->appendOption('filters', [
            'follower_class' => "\\Common\\Helper\\ViewVarMapper::followerToStyle",
            'following_class' => "\\Common\\Helper\\ViewVarMapper::followingToStyle",
            'post_class' => "\\Common\\Helper\\ViewVarMapper::postToStyle",
        ]);

        $this->_view->set('showingProfile', $showingProfile);
        $this->_view->set('userTimeline', $userTimeline);
    }
}