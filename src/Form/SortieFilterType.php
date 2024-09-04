<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class SortieFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => 'Nom de la sortie'
            ])
            ->add('startDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Start Date',
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'End Date',
            ])
            ->add('campus', EntityType::class, [
                'class' => Campus::class,
                'required' => false,
                'choice_label' => 'nom',
            ])
            ->add('isOrganizer', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties dont je suis l\'organisateur/trice',
            ])
            ->add('isInscrit', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties auquelles je suis inscrit/e',
            ])
            ->add('isNotInscrit', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties auxquelles je ne suis pas inscrit/e',
            ])
            ->add('isFinished', CheckboxType::class, [
                'required' => false,
                'label' => 'Sorties passées',
            ])
        
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
