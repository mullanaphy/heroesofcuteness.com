<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class SearchAdmin extends AbstractAdmin
{


    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Content', ['class' => 'col-md-9'])
            ->add('content', TextareaType::class, ['required' => false, 'disabled' => true, 'attr' => ['rows' => 10]])
            ->end()
            ->with('Meta', ['class' => 'col-md-3'])
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
            ->add('title', null, [
                'header_style' => 'width: 20%',
            ])
            ->add('content', null, [
                'collapse' => true,
                'header_style' => 'width: 40%',
            ])
            ->add('created', null, [
                'header_style' => 'width: 15%',
            ])
            ->add('updated', null, [
                'header_style' => 'width: 15%',
            ]);;
    }
}
