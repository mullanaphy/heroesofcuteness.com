<?php

namespace App\Admin;

use App\Entity\Comic;
use App\Entity\Character;
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
        /** @var Character $character */
        $character = $this->getSubject();

        $sourceOptions = ['required' => false];
        $thumbnailOptions = ['required' => false];
        if ($character) {
            $path = $character->getSourcePath();
            if ($path) {
                $sourceOptions['help'] = '<img src="' . $path . '" class="img-fluid" style="width:100%"/>';
                $sourceOptions['help_html'] = true;
            } else {
                $sourceOptions['required'] = true;
            }
            $path = $character->getThumbnailPath();
            if ($path) {
                $thumbnailOptions['help'] = '<img src="' . $path . '" class="img-fluid" style="width:100%"/>';
                $thumbnailOptions['help_html'] = true;
            } else {
                $thumbnailOptions['required'] = true;
            }
        }

        $form
            ->with('Credentials', ['class' => 'col-md-3'])
            ->add('name', TextType::class)
            ->add('nickname', TextType::class, ['required' => false])
            ->add('age', NumberType::class)
            ->end()
            ->with('About', ['class' => 'col-md-6'])
            ->add('sourceFile', FileType::class, $sourceOptions)
            ->add('biography', TextareaType::class, ['attr' => ['rows' => 10]])
            ->add('raw', CheckboxType::class, ['required' => false, 'label' => 'Allow raw HTML'])
            ->end()
            ->with('Meta', ['class' => 'col-md-3'])
            ->add('thumbnailFile', FileType::class, $thumbnailOptions)
            ->add('description', TextareaType::class, ['attr' => ['rows' => 5, 'maxlength' => 128]])
            ->add('comics', EntityType::class, [
                'class' => Comic::class,
                'multiple' => true,
                'by_reference' => false,
                'required' => false,
            ], [
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
        $object->refreshCreated();
    }

    protected function preUpdate(object $object): void
    {
        $object->refreshUpdated();
    }
}
