<?php

use App\Integrations\Webmotors\WebmotorsLinkParser;

it('extrai o id do final do path de um link da Webmotors', function (string $url, string $id) {
    expect(WebmotorsLinkParser::extractId($url))->toBe($id);
})->with([
    ['https://www.webmotors.com.br/comprar/chevrolet/tracker/12-turbo-premier/4-portas/2022-2023/54810001', '54810001'],
    ['https://www.webmotors.com.br/comprar/honda/civic/20-exl-cvt/4-portas/2019-2020/54810022/', '54810022'],
    ['https://www.webmotors.com.br/comprar/toyota/hilux/x/y/54810021?utm_source=ig&lkid=1#fotos', '54810021'],
    ['webmotors.com.br/comprar/vw/nivus/a/b/54810003', '54810003'],
    ['54810003', '54810003'],
]);

it('rejeita links que não são da Webmotors', function () {
    WebmotorsLinkParser::extractId('https://www.olx.com.br/anuncio/54810001');
})->throws(InvalidArgumentException::class, 'não é da Webmotors');

it('rejeita links sem id numérico', function () {
    WebmotorsLinkParser::extractId('https://www.webmotors.com.br/comprar/carros');
})->throws(InvalidArgumentException::class);

it('informa se um texto é um link válido', function () {
    expect(WebmotorsLinkParser::isWebmotorsUrl('https://www.webmotors.com.br/a/b/54810001'))->toBeTrue()
        ->and(WebmotorsLinkParser::isWebmotorsUrl('https://google.com'))->toBeFalse();
});
