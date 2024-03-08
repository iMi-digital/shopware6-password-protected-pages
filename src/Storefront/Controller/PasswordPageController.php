<?php declare(strict_types=1);

namespace ImiDiPasswordProtectedPages\Storefront\Controller;

use ImiDiPasswordProtectedPages\Service\PasswordPathService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class PasswordPageController extends StorefrontController
{

    public function __construct(
        private EntityRepository $categoryRepository,
        private GenericPageLoader $genericPageLoader,
    )
    {
    }

    /**
     * @Route("/password-site/login/{navigationId}", name="frontend.password.restricted", methods={"GET"})
     */
    public function showLogin(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $page = $this->genericPageLoader->load($request, $salesChannelContext);

        return $this->renderStorefront('@ImiDiPasswordProtectedPages/storefront/page/restricted.html.twig', [
            'navigationId' => $request->attributes->get('navigationId'),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/password-site/login/{navigationId}", name="frontend.password.login", methods={"POST"})
     */
    public function login(Request $request): Response
    {
        $navigationId = $request->attributes->get('navigationId');

        if(!$request->request->has('password')) {
            $this->addFlash(self::DANGER, $this->trans('ImiDi.password-incorrect'));
            return $this->redirectToRoute('frontend.password.restricted', ['navigationId' => $navigationId]);
        }

        $password = $request->request->get('password');

        $sitepassword = $this->getCategoryPassword($navigationId);

        if ($password === $sitepassword) {
            $request->getSession()->set(PasswordPathService::AUTH_SESSION_PREFIX . $navigationId, true);
            return $this->redirectToRoute('frontend.navigation.page', ['navigationId' => $navigationId]);
        }

        $this->addFlash(self::DANGER, $this->trans('ImiDi.password-incorrect'));

        return $this->redirectToRoute('frontend.password.restricted', ['navigationId' => $navigationId]);
    }

    private function getCategoryPassword(string $navigationId): ?string
    {
        if (!$navigationId) {
            return null;
        }

        $result = $this->categoryRepository->search(new Criteria([$navigationId]), Context::createDefaultContext());
        if ($result->count() <= 0 || !$result->first()) {
            return null;
        }

        if (!array_key_exists('password_site_password', $result->first()->getCustomFields())) {
            return null;
        }

        return $result->first()->getCustomFields()['password_site_password'];
    }
}
