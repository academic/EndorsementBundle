<?php

namespace OkulBilisim\EndorsementBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use OkulBilisim\EndorsementBundle\Entity\Skill;
use OkulBilisim\EndorsementBundle\Entity\UserEndorse;
use OkulBilisim\EndorsementBundle\Entity\UserSkill;
use OkulBilisim\EndorsementBundle\Form\Type\SkillType;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\GetResponseUserEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class SkillController extends Controller
{
    /**
     * Change user password
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $userSkills = $em->getRepository('EndorsementBundle:UserSkill')->findBy([
            'user' => $this->getUser()
        ]);

        $skillAddForm = $this->createCreateForm();
        return $this->render('EndorsementBundle:Skill:index.html.twig', [
            'userSkills' => $userSkills,
            'skillAddForm' => $skillAddForm->createView()
        ]);
    }

    /**
     * @return Form
     */
    private function createCreateForm()
    {
        $form = $this->createForm(
            new SkillType(),
            null,
            array(
                'action' => $this->generateUrl('user_endorsement_skills_add'),
                'method' => 'POST',
                'user' => $this->getUser()
            )
        );
        return $form;
    }

    /**
     * @param UserSkill $entity
     */
    public function removeAction(UserSkill $entity)
    {
        $this->throw404IfNotFound($entity);
        $em = $this->getDoctrine()->getEntityManager();
        $em->remove($entity);
        $em->flush();

        $this->successFlashBag('successful.remove');
        return $this->redirectToRoute('user_endorsement_skills');
    }

    /**
     * @param Request $request
     */
    public function addAction(Request $request)
    {
        $skillNameOrId = $request->request->get('okul_bilisim_endorsementbundle_skill')['skills'];
        $em = $this->getDoctrine()->getManager();
        $form = $this->createCreateForm();
        $form->handleRequest($request);
        $formData = $form->getData();
        if(is_int($skillNameOrId)){
            $skill = $formData['skills'];
        }else{
            $skill = new Skill();
            $skill->setName($skillNameOrId);
            $em->persist($skill);
            $em->flush();
        }
        $userSkill = new UserSkill();
        $userSkill
            ->setSkill($skill)
            ->setUser($this->getUser())
            ->setEndorsementCount(0)
            ;
        $em->persist($userSkill);
        $em->flush();

        $this->successFlashBag('success.create');
        return $this->redirectToRoute('user_endorsement_skills');
    }

    /**
     * @param Request $request
     * @param UserSkill $userSkill
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function endorseUserAction(Request $request, UserSkill $userSkill)
    {
        $em = $this->getDoctrine()->getManager();
        $endorserUser = $this->getUser();
        $endorseUser = new UserEndorse();
        $endorseUser
            ->setEndorser($endorserUser)
            ->setUser($userSkill->getUser())
            ->setSkill($userSkill->getSkill())
            ;
        $userSkill->setEndorsementCount($userSkill->getEndorsementCount()+1);
        $em->persist($endorseUser);
        $em->flush();
        $this->successFlashBag('success.create');
        return $this->redirectToRoute('ojs_user_profile', [
            'slug' => $userSkill->getUser()->getUsername()
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function autoCompleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $skillRepo = $em->getRepository("EndorsementBundle:Skill");
        $skills = $skillRepo->createQueryBuilder('s')
            ->where('s.name LIKE :skill')
            ->setParameter('skill', '%'.$request->get('q').'%')
            ->getQuery()
            ->getResult();

        $data = [];
        if(count($skills)>0){
            /** @var Skill $skill */
            foreach ($skills as $skill) {
                $data[] = [
                    'id' => $skill->getId(),
                    'text' => $skill->getName(),
                ];
            }
        }else{
            $data[] = [
                'id' => $request->get('q'),
                'text' => $request->get('q')
            ];
        }
        return new JsonResponse($data);
    }
}