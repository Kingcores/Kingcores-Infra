<?php
require_once '../../../lib/Bluefin/bluefin.php';

use Bluefin\App;
use \WBT\Business\Weixin\MpApiBusiness;



// 需要先执行 phing run_list wxtest , 以保证数据库中有测试数据

testMpRule();
//testFsRule();

//
function testMpRule()
{
    $app = \Bluefin\App::getInstance();

    $wx_wp_user_id = 12345;
    // case 1
    $userInput = '星期';
    echo "\n\n[userInput:$userInput]\n";
    $ruleID =  MpApiBusiness::matchRuleID($wx_wp_user_id,$userInput);
    echo "[ruleID:$ruleID]\n";
    $message =  MpApiBusiness::getMessageByRuleID($ruleID);
    var_dump($message);


    // case 2
    $userInput = 'Hello2BizUser';
    echo "\n\n[userInput:$userInput]\n";
    $ruleID =  MpApiBusiness::matchRuleID($wx_wp_user_id,$userInput);
    echo "[ruleID:$ruleID]\n";
    $message =  MpApiBusiness::getMessageByRuleID($ruleID);
    var_dump($message);

    // “今天” 是个前缀规则
    $userInput ='今天天气不错';
    echo "\n\n[userInput:$userInput]\n";
    $ruleID = MpApiBusiness::matchRuleID($wx_wp_user_id,$userInput);
    echo "[ruleID:$ruleID]\n";

    $message =  MpApiBusiness::getMessageByRuleID($ruleID);
    var_dump($message);

    $userInput ='哎，今天天气不好啊';
    echo "\n\n[userInput:$userInput]\n";
    $ruleID = MpApiBusiness::matchRuleID($wx_wp_user_id,$userInput);
    echo "[ruleID:$ruleID]\n";

    $message =  MpApiBusiness::getMessageByRuleID($ruleID);
    var_dump($message);

}

function testFsRule()
{

    $mpUserID = 12345;
    $userMessage = '招行客服';
    $responseToUser = 'SJDLFAJLFAJLksajldf--wx user';
    $responseFromUser = 'sjdlfajlfajlf--responseFromUser';
    MpApiBusiness::updateWxUserCurrentFsNodeID($responseToUser,0,$mpUserID);
    $ruleID = MpApiBusiness::matchRuleID($mpUserID,$userMessage);
    echo "[ruleID:$ruleID]\n";
    assert("$ruleID == 0");
    $fsRootNodeID = MpApiBusiness::matchFsRootNodeID($mpUserID,$userMessage);
    echo "[fsRootNodeID:$fsRootNodeID]\n";

    assert("$fsRootNodeID == 10");

    MpApiBusiness::processNoStateRule($mpUserID,$userMessage, $responseFromUser, $responseToUser);

    $currentFsNodeID =  MpApiBusiness::getWxUserCurrentFsNodeID($responseToUser);
    echo "currentFsNodeID($currentFsNodeID) == 10\n";

    assert("$currentFsNodeID == 10");


    $inputFsNodeID = $currentFsNodeID;
    $userMessage = '1';
    $response = MpApiBusiness::processFsStateRule($mpUserID,$userMessage, $inputFsNodeID, $responseFromUser, $responseToUser);
    echo "\ncurrentFsNodeID:$currentFsNodeID;input: $userMessage\n output:$response\n";
    $currentFsNodeID =  MpApiBusiness::getWxUserCurrentFsNodeID($responseToUser);
    echo "currentFsNodeID($currentFsNodeID) == 11\n";
    assert("$currentFsNodeID == 11");

    $inputFsNodeID = $currentFsNodeID;
    $userMessage = '1';
    $response = MpApiBusiness::processFsStateRule($mpUserID,$userMessage, $inputFsNodeID, $responseFromUser, $responseToUser);
    echo "\ncurrentFsNodeID:$currentFsNodeID;input: $userMessage\n output:$response\n";
    $currentFsNodeID =  MpApiBusiness::getWxUserCurrentFsNodeID($responseToUser);
    echo "currentFsNodeID($currentFsNodeID) == 12\n";

    assert("$currentFsNodeID == 12");


    $inputFsNodeID = $currentFsNodeID;
    $userMessage = '0';
    $response = MpApiBusiness::processFsStateRule($mpUserID,$userMessage, $inputFsNodeID, $responseFromUser, $responseToUser);
    echo "\ncurrentFsNodeID:$currentFsNodeID;input: $userMessage\n output:$response\n";
    $currentFsNodeID =  MpApiBusiness::getWxUserCurrentFsNodeID($responseToUser);
    echo "currentFsNodeID($currentFsNodeID) == 11\n";
    assert("$currentFsNodeID == 11");

    $inputFsNodeID = $currentFsNodeID;
    $userMessage = '2';
    $response = MpApiBusiness::processFsStateRule($mpUserID,$userMessage, $inputFsNodeID, $responseFromUser, $responseToUser);
    echo "\ncurrentFsNodeID:$currentFsNodeID;input: $userMessage\n output:$response\n";
    $currentFsNodeID =  MpApiBusiness::getWxUserCurrentFsNodeID($responseToUser);
    echo "currentFsNodeID($currentFsNodeID) == 11\n";

    assert("$currentFsNodeID == 11");

}