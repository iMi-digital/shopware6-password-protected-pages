<?php declare(strict_types=1);

namespace iMidiPasswordSite\Storefront\Controller;

use iMidiPasswordSite\Subscriber\CheckPasswordSubscriber;
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
    private EntityRepository $categoryRepository;
    private GenericPageLoader $genericPageLoader;

    public function __construct(EntityRepository $categoryRepository, GenericPageLoader $genericPageLoader)
    {
        $this->categoryRepository = $categoryRepository;
        $this->genericPageLoader = $genericPageLoader;
    }

    /**
     * @Route("/restricted/{navigationId}", name="frontend.password.restricted", methods={"GET"})
     */
    public function showLogin(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);

        return $this->renderStorefront('@iMidiPasswordSite/storefront/page/restricted.html.twig', [
            'navigationId' => $request->get('navigationId'),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/login", name="frontend.password.login", methods={"POST"})
     */
    public function login(Request $request, Context $context): Response
    {
        $navigationId = $request->request->get('navigationId');

        if(!$request->request->has('password')) {
            $this->addFlash(self::DANGER, $this->trans('imidi.password-incorrect'));
            return $this->redirectToRoute('frontend.password.restricted', ['navigationId' => $navigationId]);
        }

        $password = $request->request->get('password');

        $sitepassword = $this->getCategoryPassword($navigationId, $context);

        if ($password === $sitepassword) {
            $request->getSession()->set(CheckPasswordSubscriber::AUTH_SESSION_PREFIX . $navigationId, true);
            return $this->redirect($request->getSession()->get('redirect'));
        }

        $this->addFlash(self::DANGER, $this->trans('imidi.password-incorrect'));
        return $this->redirectToRoute('frontend.password.restricted', ['navigationId' => $navigationId]);
    }

    public function redirectToLogin(string $navigationId)
    {
        $response = $this->redirectToRoute('frontend.password.restricted', ['navigationId' => $navigationId]);
        $response->send();
    }

    public function getCategoryPassword(string $navigationId, Context $context): ?string
    {
        if (!$navigationId) {
            return null;
        }

        $result = $this->categoryRepository->search(new Criteria([$navigationId]), $context);
        if ($result->count() <= 0) {
            return null;
        }

        if (!array_key_exists('password_site_password', $result->first()->getCustomFields())) {
            return null;
        }

        return $result->first()->getCustomFields()['password_site_password'];
    }
}
