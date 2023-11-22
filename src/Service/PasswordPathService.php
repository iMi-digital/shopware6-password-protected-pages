<?php

namespace iMidiPasswordSite\Service;

use iMidiPasswordSite\Storefront\Controller\PasswordPageController;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheHitEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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

    public function checkPasswordInPath(CategoryEntity $category, PageLoadedEvent|HttpCacheHitEvent $event)
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

            if ($parent->getCustomFields() !== null
                && array_key_exists('password_site_password', $parent->getCustomFields())) {

                $this->checkAuthenticated($event, $parent->getId());
                break;
            }
        }
    }

    private function checkAuthenticated($event, string $navigationId)
    {
        $session = $event->getRequest()->getSession();
        $session->set('redirect', $event->getRequest()->server->get('REQUEST_URI'));

        if (!$session->has(self::AUTH_SESSION_PREFIX . $navigationId)) {
            $this->passwordPageController->redirectToLogin($navigationId);
        }
    }

    public function findNavigation(mixed $navigationId, HttpCacheHitEvent $event)
    {
        $category = $this->categoryRepository->search(new Criteria([$navigationId]), Context::createDefaultContext())->first();
        $this->checkPasswordInPath($category, $event);
    }
}
