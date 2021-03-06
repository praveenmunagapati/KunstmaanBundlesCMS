<?php

namespace Kunstmaan\ConfigBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Kunstmaan\ConfigBundle\Entity\AbstractConfig;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ConfigController
 * @package Kunstmaan\ConfigBundle\Controller
 *
 * @Route(service="kunstmaan_config.controller.config")
 */
class ConfigController
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * @var array $configuration
     */
    private $configuration;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * @var FormFactoryInterface $formFactory
     */
    private $formFactory;

    /**
     * @param RouterInterface $router
     * @param EngineInterface $templating
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param EntityManagerInterface $em
     * @param array $configuration
     * @param ContainerInterface $container
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        RouterInterface $router,
        EngineInterface $templating,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $em,
        array $configuration,
        ContainerInterface $container,
        FormFactoryInterface $formFactory
    ) {
        $this->router = $router;
        $this->templating = $templating;
        $this->authorizationChecker = $authorizationChecker;
        $this->em = $em;
        $this->configuration = $configuration;
        $this->container = $container;
        $this->formFactory = $formFactory;
    }

    /**
     * Generates the site config administration form and fills it with a default value if needed.
     *
     * @param Request $request
     * @param string $internalName
     *
     * @return array|RedirectResponse
     */
    public function indexAction(Request $request, $internalName)
    {
        /**
         * @var $entity AbstractConfig
         */
        $entity = $this->getConfigEntityByInternalName($internalName);
        $entityClass = get_class($entity);
        $formType = $entity->getDefaultAdminType();

        // Check if current user has permission for the site config.
        foreach ($entity->getRoles() as $role) {
            $this->checkPermission($role);
        }

        $repo = $this->em->getRepository($entityClass);
        $config = $repo->findOneBy(array());

        if (!$config) {
            $config = new $entityClass();
        }

        // If the formType is a service, get it from the container.
        if (!is_object($formType) && is_string($formType)) {
            $formType = $this->container->get($formType);
        }

        $formFqn = get_class($formType);

        $form = $this->formFactory->create(
            $formFqn,
            $config
        );

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->em->persist($config);
                $this->em->flush();

                return new RedirectResponse($this->router->generate('kunstmaanconfigbundle_default', array('internalName' => $internalName)));
            }
        }

        return $this->templating->renderResponse(
            '@KunstmaanConfig/Settings/configSettings.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * Get site config entity by a given internal name
     * If entity not found, throw new NotFoundHttpException()
     *
     * @param string $internalName
     *
     * @return AbstractConfig
     * @throws NotFoundHttpException
     */
    private function getConfigEntityByInternalName($internalName)
    {
        foreach ($this->configuration['entities'] as $class) {
            /** @var AbstractConfig $entity */
            $entity = new $class;

            if ($entity->getInternalName() == $internalName) {
                return $entity;
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * Check permission
     *
     * @param string $roleToCheck
     *
     * @throws AccessDeniedException
     */
    private function checkPermission($roleToCheck = 'ROLE_SUPER_ADMIN')
    {
        if (false === $this->authorizationChecker->isGranted($roleToCheck)) {
            throw new AccessDeniedException();
        }
    }
}
