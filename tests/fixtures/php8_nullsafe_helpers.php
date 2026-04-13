<?php

declare(strict_types=1);

namespace Tests\Fixtures;

function getHtmlNullsafeCircleId($dom): ?string
{
    return $dom->findOneOrNull('svg')?->findOneOrNull('circle')?->getAttribute('id');
}

function getHtmlNullsafeMissingId($dom): ?string
{
    return $dom->findOneOrNull('svg')?->findOneOrNull('path')?->getAttribute('id');
}

function getXmlNullsafeTitle($xmlParser): ?string
{
    return $xmlParser->findOneOrNull('//chapter')?->findOneOrNull('//chap:title')?->text();
}

function getXmlNullsafeMissingTitle($xmlParser): ?string
{
    return $xmlParser->findOneOrNull('//chapter')?->findOneOrNull('//chap:foo')?->text();
}
