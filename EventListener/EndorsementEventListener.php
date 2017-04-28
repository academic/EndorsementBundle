<?php

namespace Vipa\EndorsementBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Vipa\CoreBundle\Events\TwigEvent;
use Vipa\JournalBundle\Service\JournalService;
use Vipa\JournalBundle\Entity\Journal;
use Vipa\CoreBundle\Event\WorkflowEvent;
use Vipa\CoreBundle\Events\TwigEvents;
use Vipa\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EndorsementEventListener implements EventSubscriberInterface
{
    /**
     * @var  ObjectManager
     */
    private $em;

    /**
     * @var  RouterInterface
     */
    private $router;

    /**
     * @var  JournalService
     */
    private $journalService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param ObjectManager   $em
     * @param RouterInterface $router
     * @param JournalService  $journalService
     */
    public function __construct(
        ObjectManager $em,
        RouterInterface $router,
        JournalService $journalService,
        TokenStorageInterface $tokenStorage,
        \Twig_Environment $twig,
        TranslatorInterface $translator
    )
    {
        $this->em               = $em;
        $this->router           = $router;
        $this->journalService   = $journalService;
        $this->tokenStorage     = $tokenStorage;
        $this->twig             = $twig;
        $this->translator       = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            TwigEvents::VIPA_USER_PROFILE_EDIT_TABS          => 'onUserProfileEditTabs',
            TwigEvents::VIPA_USER_PROFILE_PUBLIC_VIEW        => 'onUserProfilePublicView',
            TwigEvents::VIPA_USER_PROFILE_PUBLIC_VIEW_SCRIPT => 'onUserProfilePublicViewScript'
        );
    }

    /**
     * @param TwigEvent $event
     */
    public function onUserProfileEditTabs(TwigEvent $event)
    {
        $isActive = $event->getOptions()['active_tab'] == 100 ? 'class="active"':'';
        $event->setTemplate('<li role="presentation" '.$isActive.'>'
            .'<a href="'.$this->router->generate('user_endorsement_skills').'">'.$this->translator->trans('title.skills').'</a>'
        .'</li>');
    }

    /**
     * @param TwigEvent $event
     */
    public function onUserProfilePublicView(TwigEvent $event)
    {
        $options = $event->getOptions();
        $token = $this->tokenStorage->getToken();
        /** @var User $currentUser */
        $currentUser = $token->getUser();
        $user = $options['user'];
        $template = '';
        $userSkills = $this->em->getRepository('VipaEndorsementBundle:UserSkill')->findBy([
            'user' => $user
        ]);
        if(!$currentUser instanceof User){
            $isCurrentUser = true;
        }else{
            $isCurrentUser = $currentUser->getId() == $user->getId() ? true: false;
        }
        $event->setTemplate($this->twig->render('VipaEndorsementBundle:Skill:skills.html.twig', [
            'userSkills' => $userSkills,
            'user' => $user,
            'isCurrentUser' => $isCurrentUser
        ]));
    }

    /**
     * @param TwigEvent $event
     */
    public function onUserProfilePublicViewScript(TwigEvent $event)
    {
        $options = $event->getOptions();
        $token = $this->tokenStorage->getToken();
        /** @var User $currentUser */
        $currentUser = $token->getUser();
        $user = $options['user'];
        if(!$currentUser instanceof User){
            $isCurrentUser = true;
        }else{
            $isCurrentUser = $currentUser->getId() == $user->getId() ? true: false;
        }
        $event->setTemplate($this->twig->render('VipaEndorsementBundle:Skill:skills_script.html.twig', [
            'isCurrentUser' => $isCurrentUser
        ]));
    }
}
