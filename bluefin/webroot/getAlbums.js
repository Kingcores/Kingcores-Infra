var casper = require('casper').create(),
    args = casper.cli.args,
    fs = require('fs'),
    qzonePhotoUrlBase = 'http://user.qzone.qq.com/';

var qq = args[0],
    url = qzonePhotoUrlBase + qq + '/photo';

if (args.length < 1) {
    casper.echo("Usage:");
    casper.echo("    casperjs getAlbums.js <QQ>");
    casper.echo("");
    casper.exit();
}

casper.on('url.changed', function(url) {
    casper.echo(url, 'INFO');
});

casper.start(url, function () {

    var result = false;

    this.waitFor(function () {
        result = this.evaluate(function(qq) {
            var tphoto = document.getElementById('tphoto').contentDocument;
            if (!tphoto) { return false; }

            var list = tphoto.getElementsByClassName('tcisd_albumlist');
            if (!list) { return false; }

            var l = list.length;
            if (!l) { return false; }

            var lastImg = list[l-1].getElementsByTagName('img');
            if (lastImg.length == 0 || lastImg[0].attributes['src'].value == '/ac/b.gif') { return false; }

            var title = document.getElementById('top_head_title').childNodes[0].innerText,
                result = {qq: qq, title: title},
                albums = [];

            for (var i = 0; i < l; i++) {
                var album = {
                    title: list[i].parentNode.parentNode.parentNode.getElementsByClassName('photo_albumlist_name')[0].getElementsByTagName('a')[0].attributes['title'].value,
                    url: list[i].attributes['href'].value,
                    thumbnail: list[i].getElementsByTagName('img')[0].attributes['src'].value
                };

                albums.push(album);
            }

            result['albums'] = albums;

            return JSON.stringify(result);
        }, qq);

        return result;
    });

    this.then(function () {

        if (result) {
            fs.makeDirectory(qq);
            fs.write(qq + '/albums.json', result, 'w');

            tphoto = JSON.parse(result);
            for (var index in tphoto.albums) {
                var album = tphoto.albums[index],
                    path = qq + '/' + index,
                    id = album.url.replace(url + '/', '');
                fs.makeDirectory(path);
                album['id'] = id;
                fs.write(path + '/album.json', JSON.stringify(album), 'w');
                this.echo(album.thumbnail);
                this.download(album.thumbnail, path + '/thumbnail.html');
            }

            this.echo('Done.');
        } else {
            this.echo('Failed!', 'ERROR');
        }
    });
});

casper.run();

/*
page.open(url, function (status) {

    waitFor(function () {
        // Check in the page if a specific element is now visible
        return page.evaluate(function(qq) {
            var tphoto = document.getElementById('tphoto').contentDocument;
            if (!tphoto) { return false; }

            var list = tphoto.getElementsByClassName('tcisd_albumlist');
            if (!list) { return false; }

            var l = list.length;
            if (!l) { return false; }

            var lastImg = list[l-1].getElementsByTagName('img');
            if (lastImg.length == 0 || lastImg[0].attributes['src'].value == '/ac/b.gif') { return false; }

            var title = document.getElementById('top_head_title').childNodes[0].innerText,
                result = {qq: qq, title: title},
                albums = [];

            for (var i = 0; i < l; i++) {
                var album = {
                    title: list[i].parentNode.parentNode.parentNode.getElementsByClassName('photo_albumlist_name')[0].getElementsByTagName('a')[0].attributes['title'].value,
                    url: list[i].attributes['href'].value,
                    thumbnail: list[i].getElementsByTagName('img')[0].attributes['src'].value
                };

                albums.push(album);
            }

            result['albums'] = albums;

            return JSON.stringify(result);
        }, qq);
    }, function (ready) {
        if (ready) {

            fs.makeDirectory(qq);
            fs.write(qq + '/albums.json', ready, 'w');

            tphoto = JSON.parse(ready);
            for (var index in tphoto.albums) {
                var album = tphoto.albums[index],
                    path = qq + '/' + index,
                    id = album.url.replace(url + '/', '');
                fs.makeDirectory(path);
                album['id'] = id;
                fs.write(path + '/album.json', JSON.stringify(album), 'w');
            }

            console.log('Done.');

        } else {
            console.log('Failed!');
        }
        phantom.exit();
    });
});
*/