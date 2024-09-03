<?php

namespace App\Admin;

use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ComicAdmin extends AbstractAdmin
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Content', ['class' => 'col-md-9'])
            ->add('title', TextType::class)
            ->add('content', TextareaType::class, ['required' => false, 'attr' => ['rows' => 10]])
            ->add('raw', CheckboxType::class, ['required' => false, 'label' => 'Allow raw HTML'])
            ->end()
            ->with('Meta', ['class' => 'col-md-3'])
            ->add('author', EntityType::class, ['class' => User::class, 'required' => true, 'empty_data' => $this->tokenStorage->getToken()->getUser(), 'choice_label' => 'username'])
            ->end();
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('title', null, [
                'route' => [
                    'name' => 'edit'
                ]
            ])
            ->add('created')
            ->add('updated');
    }
}
