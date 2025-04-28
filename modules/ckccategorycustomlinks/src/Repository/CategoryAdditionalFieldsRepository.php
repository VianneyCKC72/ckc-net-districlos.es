<?php
/**
 * 2007-2024 PrestaShop
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
 *  @copyright 2007-2024 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

declare(strict_types=1);

namespace PrestaShop\Module\Ckccategorycustomlinks\Repository;

use Doctrine\ORM\EntityRepository;
use PrestaShop\Module\Ckccategorycustomlinks\Entity\CategoryAdditionalFields;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class CategoryAdditionalFieldsRepository
 * @package PrestaShop\Module\Ckccategorycustomlinks\Repository
 */
class CategoryAdditionalFieldsRepository extends EntityRepository
{

    /**
     * @param $categoryId
     * @param $categoryFieldContent
     */
    public function insertOrUpdateCategoryFieldContent($categoryId, $categoryFieldContent)
    {

        $CategoryAdditionalFields = $this->findOneBy(['categoryId' => $categoryId]);

        $needToPerist = false;
        if (!$CategoryAdditionalFields) {
            $CategoryAdditionalFields = new CategoryAdditionalFields();
            $CategoryAdditionalFields->setCategoryId($categoryId);
            $needToPerist = true;
        }

        if (null !== $categoryFieldContent){
            $serializedData = base64_encode(json_encode($categoryFieldContent));

            $CategoryAdditionalFields->setCategoryFieldContent($serializedData);
    
            $em = $this->getEntityManager();
            if ($needToPerist) $em->persist($CategoryAdditionalFields);
            $em->flush();
        } else {
            $em = $this->getEntityManager();
            $em->remove($CategoryAdditionalFields);
            $em->flush();
        }



    }

}
