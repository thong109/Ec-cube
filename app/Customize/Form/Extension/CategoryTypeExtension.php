<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Form\Extension;

use Eccube\Form\Type\Admin\CategoryType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class CategoryTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category_name_en', TextType::class)
            ->add('category_file', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('category_image', HiddenType::class, [
                'required' => false,
            ])
        ;  
    }
     /**
     * {@inheritdoc}
     * 
     * @return string
     */
    public function getExtendedType(){
        return CategoryType::class;
    }

    public static function getExtendedTypes()
    {
        yield CategoryType::class;
    }
}
