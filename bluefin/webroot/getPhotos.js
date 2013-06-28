var casper = require('casper').create(),
    args = casper.cli.args,
    qzonePhotoUrlBase = 'http://user.qzone.qq.com/';

if (args.length < 2) {
    casper.echo("Usage:");
    casper.echo("    casperjs getPhotos.js <qq> <id>");
    casper.echo("");
    casper.exit();
}

var qq = args[0],
    id = args[1],
    url = qzonePhotoUrlBase + qq + '/photo/' + id;

casper.on('url.changed', function(url) {
    casper.echo(url);
});

casper.start(url, function () {

    this.waitForSelector('#tphoto');

    this.withFrame(1, function() {
        this.waitForSelector('#photo_list p.photo_name > a');

        this.then(function () {
            var photoCount = this.evaluate(
                return document
            )

        });

        this.thenClick('#photo_list p.photo_name > a:first-child', function () {

            this.waitForSelector('#tphoto');

            this.withFrame(1, function() {

            });
        });
    });

    /*
    this.then(function() {
        var photos = this.evaluate(function() {
            var tphoto = document.getElementById('tphoto').contentDocument,
                list = tphoto.querySelectorAll('#photo_list > li > div');

            for (var i = 0; i < l; i++) {
                var photo = {
                    title: list[i].querySelector('p.photo_name > a').attributes['title'].value,
                    url: list[i].attributes['href'].value,
                    thumbnail: list[i].getElementsByTagName('img')[0].attributes['src'].value
                };

                albums.push(album);
            }

            return list.innerHTML;
        });

        this.echo(images);
    });
    */

    /*


    this.then(function () {
        var images = this.evaluate(function() {
            var tphoto = document.getElementById('tphoto').contentDocument,
                list = tphoto.getElementById('photo_list');

            var images = list.querySelectorAll('p.photo_photolist_img > a > img');
            return images[0].attributes['src'];
        });

        this.echo(images);
    });
    */

    /*
    this.click('ul#photo_list p.photo_name a');

    this.echo('2');

    this.waitUntilVisible('div#photo_layout_section');

    this.echo('3');

    this.captureSelector('test.png', 'div#photo_layout_section');

    this.echo('done');
    */
});


casper.run();
