<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Controller;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Controller\Content\PreviewController as BasePreviewController;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Netgen\Bundle\EzPlatformSiteApiBundle\Routing\UrlAliasRouter;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\Core\Site\Values\Location as SiteLocation;
use Symfony\Component\HttpFoundation\Request;

class PreviewController extends BasePreviewController
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @param \Netgen\EzPlatformSiteApi\API\LoadService $loadService
     */
    public function setLoadService(LoadService $loadService): void
    {
        $this->loadService = $loadService;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     */
    protected function getForwardRequest(Location $location, Content $content, SiteAccess $previewSiteAccess, Request $request, $language): Request
    {
        $request = parent::getForwardRequest($location, $content, $previewSiteAccess, $request, $language);

        $overrideViewAction = $this->configResolver->getParameter(
            'override_url_alias_view_action',
            'netgen_ez_platform_site_api',
            $previewSiteAccess->name
        );

        // If the preview siteaccess is configured in legacy_mode
        // we forward to the LegacyKernelController.
        // For compatibility with eZ Publish Legacy
        if ($this->isLegacyModeSiteAccess($previewSiteAccess->name)) {
            $request->attributes->set('_controller', 'ezpublish_legacy.controller:indexAction');
        } elseif ($overrideViewAction) {
            $request->attributes->set('_controller', UrlAliasRouter::OVERRIDE_VIEW_ACTION);

            $this->injectSiteApiValueObjects($request, $language);
        }

        return $request;
    }

    /**
     * Injects the Site API value objects into request, replacing the original
     * eZ API value objects.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $language
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     */
    protected function injectSiteApiValueObjects(Request $request, string $language): void
    {
        /** @var \eZ\Publish\API\Repository\Values\Content\Content $content */
        /** @var \eZ\Publish\API\Repository\Values\Content\Location $location */
        $content = $request->attributes->get('content');
        $location = $request->attributes->get('location');

        $siteContent = $this->loadService->loadContent(
            $content->id,
            $content->versionInfo->versionNo,
            $language
        );

        if (!$location->isDraft()) {
            $siteLocation = $this->loadService->loadLocation($location->id);
        } else {
            $siteLocation = new SiteLocation([
                'contentInfo' => $siteContent->contentInfo,
                'innerLocation' => $location,
            ]);
        }

        $requestParams = $request->attributes->get('params');
        $requestParams['content'] = $siteContent;
        $requestParams['location'] = $siteLocation;

        $request->attributes->set('content', $siteContent);
        $request->attributes->set('location', $siteLocation);
        $request->attributes->set('params', $requestParams);
    }

    /**
     * Returns if the provided siteaccess is running in legacy mode.
     *
     * @param string $siteAccessName
     *
     * @return bool
     */
    protected function isLegacyModeSiteAccess(string $siteAccessName): bool
    {
        if (!$this->configResolver->hasParameter('legacy_mode', 'ezsettings', $siteAccessName)) {
            return false;
        }

        return $this->configResolver->getParameter('legacy_mode', 'ezsettings', $siteAccessName);
    }
}
