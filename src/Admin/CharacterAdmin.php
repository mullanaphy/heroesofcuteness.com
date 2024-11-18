<?php

namespace App\Admin;

use App\Entity\Comic;
use App\Entity\Panel;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CharacterAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        /** @var Panel $panel */
        $panel = $this->getSubject();

        $fileOptions = ['required' => false];
        if ($panel && ($path = $panel->getPath())) {
            $fileOptions['help'] = '<img src="' . $path . '" class="img-fluid" style="width:100%"/>';
            $fileOptions['help_html'] = true;
        }

        $form
            ->with('Credentials', ['class' => 'col-md-3'])
            ->add('name', TextType::class)
            ->add('nickname', TextType::class, ['required' => false])
            ->add('age', NumberType::class)
            ->end()
            ->with('About', ['class' => 'col-md-6'])
            ->add('file', FileType::class, $fileOptions)
            ->add('biography', TextareaType::class, ['attr' => ['rows' => 10]])
            ->add('raw', CheckboxType::class, ['required' => false, 'label' => 'Allow raw HTML'])
            ->end()
            ->with('Meta', ['class' => 'col-md-3'])
            ->add('description', TextareaType::class, ['attr' => ['rows' => 5, 'maxlength' => 128]])
            ->add('comics', EntityType::class, [
                'class' => Comic::class,
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
                    'name' => 'edit'
                ],
                'row_align' => 'left',
                'header_style' => 'width: 5%',
            ])
            ->add('name', null, [
                'header_style' => 'width: 20%',
            ])
            ->add('nickname', null, [
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
        $object->refreshUpdated();
    }

    protected function preUpdate(object $object): void
    {
        $object->refreshUpdated();
    }
}
