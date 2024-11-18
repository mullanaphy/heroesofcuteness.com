<?php

namespace App\Admin;

use App\Entity\Comic;
use App\Entity\Panel;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PanelAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        /** @var Panel $panel */
        $panel = $this->getSubject();

        $fileOptions = ['required' => false];
        if ($panel && ($path = $panel->getSourcePath())) {
            $fileOptions['help'] = '<img src="' . $path . '" class="img-fluid" style="width:100%"/>';
            $fileOptions['help_html'] = true;
        } else {
            $fileOptions['required'] = true;
        }

        $form
            ->with('Content', ['class' => 'col-md-9'])
            ->add('file', FileType::class, $fileOptions)
            ->add('alt', TextType::class, ['required' => false])
            ->add('dialogue', TextareaType::class, ['required' => false, 'attr' => ['rows' => 10]])
            ->end()
            ->with('Meta', ['class' => 'col-md-3'])
            ->add('comic', EntityType::class, ['class' => Comic::class])
            ->add('sort', NumberType::class)
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
            ->add('alt', null, [
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
        $object->refreshUpdated();
    }

    protected function preUpdate(object $object): void
    {
        $object->refreshUpdated();
    }
}
