<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Category")
 */
trait CategoryTrait
{
    /**
     * @var string|null
     * @ORM\Column(name="category_name_en",type="string", length=255 ,nullable=true)
     */
    private $category_name_en = null;

    /**
     * Set category_name_en
     * @param string $category_name_en|null
     */

    public function setCategoryNameEn($category_name_en = null)
    {
        $this->category_name_en = $category_name_en;
        return $this;
    }
    /**
     * Get category_name_en
     * @return string
     */
    public function getCategoryNameEn()
    {
        return $this->category_name_en;
    }

    /**
     * @var string
     * @ORM\Column(name="category_image",type="string", length=255 ,nullable=true)
     */
    private $category_image;
    /**
     * Set categoryImage.
     *
     * @param string|null $categoryImage
     *
     * @return Category
     */
    public function setCategoryImage($categoryImage = null)
    {
        $this->category_image = $categoryImage;

        return $this;
    }

    /**
     * Get categoryImage.
     *
     * @return string|null
     */
    public function getCategoryImage()
    {
        return $this->category_image;
    }
}
