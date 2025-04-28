<?php
/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

 declare(strict_types=1);

namespace PrestaShop\Module\Ckccategorycustomlinks\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @ORM\Table(name="ps_ckc_category_additional_fields")
 * @ORM\Entity(repositoryClass="PrestaShop\Module\Ckccategorycustomlinks\Repository\CategoryAdditionalFieldsRepository")
 */

 class CategoryAdditionalFields
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @ORM\Column(name="id_category", type="integer")
     */
    private $categoryId;

    /**
     * @var string
     *
     * @ORM\Column(name="category_field_content", type="string")
     */
    private $categoryFieldContent;


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param mixed $categoryId
     */
    public function setCategoryId($categoryId): void
    {
        $this->categoryId = $categoryId ;
    }

    /**
     * @return string
     */
    public function getCategoryFieldContent() : ?string
    {
        return $this->categoryFieldContent;
    }

    /**
     * @param string $categoryFieldContent
     */
    public function setCategoryFieldContent(?string $categoryFieldContent): void
    {
        $this->categoryFieldContent = $categoryFieldContent;
    }

}
