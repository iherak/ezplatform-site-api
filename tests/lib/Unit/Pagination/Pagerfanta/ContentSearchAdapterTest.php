<?php

namespace Netgen\EzPlatformSite\Tests\Unit\Pagination\Pagerfanta;

use Netgen\EzPlatformSite\Core\Site\Pagination\Pagerfanta\ContentSearchAdapter;
use eZ\Publish\API\Repository\Values\Content\Query;

class ContentSearchAdapterTest extends ContentSearchHitAdapterTest
{
    protected function getAdapter(Query $query)
    {
        return new ContentSearchAdapter($query, $this->findService);
    }

    protected function getExpectedSlice($hits)
    {
        $expectedResult = [];

        /** @var \eZ\Publish\API\Repository\Values\Content\Search\SearchHit[] $hits */
        foreach ($hits as $hit) {
            $expectedResult[] = $hit->valueObject;
        }

        return $expectedResult;
    }
}
