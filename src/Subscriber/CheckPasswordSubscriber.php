<?php declare(strict_types=1);

namespace ImiDiPasswordProtectedPages\Subscriber;

use ImiDiPasswordProtectedPages\Exception\UnauthorizedException;
use ImiDiPasswordProtectedPages\Service\PasswordPathService;
use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheHitEvent as CoreHttpCacheHitEvent;
use Shopware\Storefront\Framework\Cache\Event\HttpCacheHitEvent;
use Shopware\Storefront\Framework\Routing\Router;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

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
            /** @deprecated tag:v6.6.0 - Delete HttpCacheHitEvent and use CoreHttpCacheHitEvent instead */
            // phpcs:ignore
            HttpCacheHitEvent::class => 'onCachedPageLoaded',
            CoreHttpCacheHitEvent::class => 'onCachedPageLoaded',
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onPageLoaded(PageLoadedEvent $event)
    {
        $request = $event->getRequest();

        //early return for login page being loaded
        if ($request->attributes->get('_route') === 'frontend.password.restricted') {
            return;
        }

        $page = $event->getPage();
        if (!$page->getHeader()) {
            return;
        }

        $activeNavigation = $page->getHeader()->getNavigation()->getActive();
        $this->passwordPathService->checkPasswordInPath($activeNavigation, $event);
    }

    // phpcs:ignore
    public function onCachedPageLoaded(HttpCacheHitEvent|CoreHttpCacheHitEvent $event)
    {
        if ($this->router instanceof RequestMatcherInterface) {
            // phpcs:ignore
            $parameters = $this->router->matchRequest($event->getRequest());
        } else {
            // phpcs:ignore
            $parameters = $this->router->match($event->getRequest()->getPathInfo());
        }

        if ($parameters['_route'] === 'frontend.navigation.page') {
            $this->passwordPathService->findNavigation($parameters['navigationId'], $event);
        }
    }

    public function onException(ExceptionEvent $event)
    {
        if(!$event->getThrowable() instanceof UnauthorizedException) {
            return;
        }

        $request = $event->getRequest();

        $parameters = [
            'redirectTo' => $request->attributes->get('_route'),
            'redirectParameters' => json_encode($request->attributes->get('_route_params'), \JSON_THROW_ON_ERROR),
            'navigationId' => $request->attributes->get('_route_params')['navigationId'] ?? '',
        ];

        $redirectResponse = new RedirectResponse($this->router->generate('frontend.password.restricted', $parameters));

        $event->setResponse($redirectResponse);
    }
}
