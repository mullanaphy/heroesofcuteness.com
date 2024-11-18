<?php

namespace App\Admin;

use App\Entity\Comic;
use App\Entity\Character;
use App\Entity\User;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
        /** @var Comic $comic */
        $comic = $this->getSubject();

        $thumbnailOptions = ['required' => false];
        if ($comic && ($path = $comic->getThumbnailPath())) {
            $thumbnailOptions['help'] = '<img src="' . $path . '" class="img-fluid" style="width:100%"/>';
            $thumbnailOptions['help_html'] = true;
        } else {
            $thumbnailOptions['required'] = true;
        }

        $form
            ->with('Content', ['class' => 'col-md-9'])
            ->add('title', TextType::class)
            ->add('content', TextareaType::class, ['required' => false, 'attr' => ['rows' => 10]])
            ->add('raw', CheckboxType::class, ['required' => false, 'label' => 'Allow raw HTML'])
            ->end()
            ->with('Meta', ['class' => 'col-md-3'])
            ->add('author', EntityType::class, ['class' => User::class, 'required' => true, 'empty_data' => $this->tokenStorage->getToken()->getUser(), 'choice_label' => 'username'])
            ->add('thumbnailFile', FileType::class, $thumbnailOptions)
            ->add('description', TextareaType::class, ['attr' => ['rows' => 5, 'maxlength' => 128]])
            ->add('characters', EntityType::class, [
                'class' => Character::class,
                'multiple' => true,
                'by_reference' => false,
                'required' => false,
            ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                    'sortable' => 'position',
                ])
            ->add('created', DateTimeType::class, ['disabled' => true])
            ->add('updated', DateTimeType::class, ['disabled' => true])
            ->end();
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, [
                'route' => [
                    'name' => 'edit',
                ],
                'row_align' => 'left',
                'header_style' => 'width: 5%',
            ])
            ->add('title', null, [
                'header_style' => 'width: 20%',
            ])
            ->add('description', null, [
                'collapse' => true,
                'header_style' => 'width: 40%',
            ])
            ->add('created', null, [
                'header_style' => 'width: 15%',
            ])
            ->add('updated', null, [
                'header_style' => 'width: 15%',
            ]);
    }

    protected function prePersist(object $object): void
    {
        $object->refreshCreated();
    }

    protected function preUpdate(object $object): void
    {
        $object->refreshUpdated();
    }
}
