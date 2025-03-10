<?php

namespace App\Form;

use App\Entity\Artist;
use App\Entity\Release;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArtistType extends AbstractType
{
    public function __construct(private SluggerInterface $slugger) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [])
            ->add('slug', TextType::class, [])
            ->add('thumbnail')
            ->add('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->add('updatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('releases', EntityType::class, [
                'class' => Release::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->addEventListener(
                FormEvents::PRE_SUBMIT,
                $this->autoSlug(...)
            )
            ->add('save', SubmitType::class, [
                'label' => 'Save'
            ])
        ;
    }

    public function autoSlug(PreSubmitEvent $event): void
    {
        $data = $event->getData();

        if (empty($data['slug'])) {
            $data['slug'] = $this->slugger->slug($data['name']);
            $event->setData($data);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Artist::class,
        ]);
    }
}
