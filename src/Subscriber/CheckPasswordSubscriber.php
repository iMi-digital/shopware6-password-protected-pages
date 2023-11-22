<?php declare(strict_types=1);

namespace iMidiPasswordSite\Subscriber;

use iMidiPasswordSite\Service\PasswordPathService;
use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheHitEvent;
use Shopware\Storefront\Framework\Routing\Router;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckPasswordSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private Router $router,
        private PasswordPathService $passwordPathService,
    )
    {
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

        //early return for login page being loaded
        if ($request->attributes->get('_route') === 'frontend.password.restricted') {
            return;
        }

        //????
        $page = $event->getPage();
        if (!$page->getHeader()) {
            return;
        }

        $activeNavigation = $page->getHeader()->getNavigation()->getActive();
        $this->passwordPathService->checkPasswordInPath($activeNavigation, $event);
    }

    public function onCachedPageLoaded(HttpCacheHitEvent $event)
    {

        $requestUri = $event->getRequest()->attributes->get('resolved-uri');

        if ($this->router instanceof RequestMatcherInterface) {
            $parameters = $this->router->matchRequest($event->getRequest());
        } else {
            $parameters = $this->router->match($event->getRequest()->getPathInfo());
        }

        if ($parameters['_route'] === 'frontend.navigation.page') {
            $navigationId = $parameters['navigationId'];
            $this->passwordPathService->findNavigation($navigationId, $event);
        }
    }


}
