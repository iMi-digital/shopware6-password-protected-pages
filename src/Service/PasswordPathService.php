<?php

namespace ImiDiPasswordSite\Service;

use ImiDiPasswordSite\Exception\UnauthorizedException;
use ImiDiPasswordSite\Storefront\Controller\PasswordPageController;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheHitEvent as CoreHttpCacheHitEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Framework\Cache\Event\HttpCacheHitEvent;
use Shopware\Storefront\Page\PageLoadedEvent;

class PasswordPathService
{

    const AUTH_SESSION_PREFIX = 'auth_';

    public function __construct(
        private PasswordPageController $passwordPageController,
        private EntityRepository $categoryRepository,
    )
    {
    }

    public function checkPasswordInPath(CategoryEntity $category, PageLoadedEvent|HttpCacheHitEvent|CoreHttpCacheHitEvent $event)
    {
        $context = Context::createDefaultContext();

        $path = $category->getPath();
        if (!$path) {
            if ($category->getCustomFields() !== null && array_key_exists('password_site_password', $category->getCustomFields())) {
                $this->checkAuthenticated($event, $category->getId());
            }
            return;
        }

        $path .= $category->getId();
        $parents = array_reverse(array_slice(explode('|', $path), 1));


        foreach ($parents as $parentId) {

            $parent = $this->categoryRepository
                ->search(new Criteria([$parentId]), $context)->first();

            if(!$parent) {
                break;
            }

            if ($parent->getCustomFields() !== null
                && array_key_exists('password_site_password', $parent->getCustomFields())) {

                $this->checkAuthenticated($event, $parent->getId());
                break;
            }
        }
    }

    private function checkAuthenticated(PageLoadedEvent|HttpCacheHitEvent|CoreHttpCacheHitEvent $event, string $navigationId)
    {
        $session = $event->getRequest()->getSession();
        $redirect = $event->getRequest()->server->get('REQUEST_URI');
        $session->set('redirect', $redirect);

        if (!$session->has(self::AUTH_SESSION_PREFIX . $navigationId)) {
            throw new UnauthorizedException(302, 403, 'Unauthorized access to password protected page', ['location' => '/restricted/' . $navigationId]);
        }
    }

    public function findNavigation(mixed $navigationId, PageLoadedEvent|HttpCacheHitEvent|CoreHttpCacheHitEvent $event)
    {
        $category = $this->categoryRepository->search(new Criteria([$navigationId]), Context::createDefaultContext())->first();
        $this->checkPasswordInPath($category, $event);
    }
}
