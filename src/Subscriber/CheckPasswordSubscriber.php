<?php declare(strict_types=1);

namespace iMidiPasswordSite\Subscriber;

use iMidiPasswordSite\Storefront\Controller\PasswordPageController;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Framework\Cache\Event\HttpCacheHitEvent;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckPasswordSubscriber implements EventSubscriberInterface
{
    public const AUTH_SESSION_PREFIX = 'auth_';

    private EntityRepository $categoryRepositoryInterface;
    private PasswordPageController $passwordPageController;

    public function __construct(EntityRepository $categoryRepositoryInterface, PasswordPageController $passwordPageController)
    {
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->passwordPageController = $passwordPageController;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onPageLoaded',
            HttpCacheHitEvent::class => 'onCachedPageLoaded',
        ];
    }

    public function onPageLoaded(PageLoadedEvent $event)
    {
        $request = $event->getRequest();
        if ($request->attributes->get('_route') === 'frontend.password.restricted') {
            return;
        }

        $page = $event->getPage();
        if (!$page->getHeader()) {
            return;
        }

        $activeNavigation = $page->getHeader()->getNavigation()->getActive();
        $this->checkPasswordInPath($activeNavigation, $event);
    }

    public function onCachedPageLoaded(HttpCacheHitEvent $event)
    {
        $navigationId = basename($event->getRequest()->getRequestUri());

        $category = $this->categoryRepositoryInterface->search(new Criteria([$navigationId]), Context::createDefaultContext())->first();

        $this->checkPasswordInPath($category, $event);
    }

    private function checkAuthenticated(PageLoadedEvent|HttpCacheHitEvent $event, string $navigationId)
    {
        $session = $event->getRequest()->getSession();
        $session->set('redirect', $event->getRequest()->server->get('REQUEST_URI'));

        if (!$session->has(self::AUTH_SESSION_PREFIX . $navigationId)) {
            $this->passwordPageController->redirectToLogin($navigationId);
        }
    }

    private function checkPasswordInPath(CategoryEntity $category, PageLoadedEvent|HttpCacheHitEvent $event)
    {
        $context = Context::createDefaultContext();
        if ($event instanceof PageLoadedEvent) {
            $context = $event->getContext();
        }

        $path = $category->getPath();
        if (!$path) {
            if (array_key_exists('password_site_password', $category->getCustomFields())) {
                $this->checkAuthenticated($event, $category->getId());
            }
            return;
        }

        $path .= $category->getId();
        $parents = array_reverse(array_slice(explode('|', $path), 1));
        foreach ($parents as $parentId) {
            $parent = $this->categoryRepositoryInterface
                ->search(new Criteria([$parentId]), $context)->first();
            if (array_key_exists('password_site_password', $parent->getCustomFields())) {
                $this->checkAuthenticated($event, $parent->getId());
                break;
            }
        }
    }

}
