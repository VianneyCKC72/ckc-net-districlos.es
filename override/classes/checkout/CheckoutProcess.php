<?php
/**
 * Enrichit les métadonnées des étapes pour le fil d'Ariane checkout.
 */
class CheckoutProcess extends CheckoutProcessCore
{
    /**
     * @param array $extraParams
     *
     * @return string
     *
     * @throws SmartyException
     */
    public function render(array $extraParams = [])
    {
        $scope = $this->context->smarty->createData(
            $this->context->smarty
        );

        $params = [
            'steps' => array_map(static function (CheckoutStepInterface $step) {
                return [
                    'identifier' => $step->getIdentifier(),
                    'title' => $step->getTitle(),
                    'is_current' => (bool) $step->isCurrent(),
                    'is_complete' => (bool) $step->isComplete(),
                    'is_reachable' => (bool) $step->isReachable(),
                    'ui' => new PrestaShop\PrestaShop\Core\Foundation\Templating\RenderableProxy($step),
                ];
            }, $this->getSteps()),
        ];

        $scope->assign(array_merge($extraParams, $params));

        $tpl = $this->context->smarty->createTemplate(
            $this->getTemplate(),
            $scope
        );

        return $tpl->fetch();
    }
}
