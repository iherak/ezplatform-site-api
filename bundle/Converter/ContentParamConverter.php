<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Converter;

use Netgen\EzPlatformSiteApi\API\Values\Content;

final class ContentParamConverter extends SiteParamConverter
{
    protected function getSupportedClass(): string
    {
        return Content::class;
    }

    protected function getPropertyName(): string
    {
        return 'contentId';
    }

    protected function loadValueObject(int $id): Content
    {
        return $this->loadService->loadContent($id);
    }
}
