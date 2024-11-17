<?php

namespace App\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class SearchAdmin extends AbstractAdmin
{


    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Content', ['class' => 'col-md-9'])
            ->add('content', TextareaType::class, ['required' => false, 'attr' => ['rows' => 10]])
            ->end();
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, [
                'route' => [
                    'name' => 'edit'
                ]
            ]);
    }
}
