<?php

namespace Kevin\Payment\Plugin\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

/**
 * Class CsrfValidatorSkip.
 */
class CsrfValidatorSkip
{
    /**
     * @return bool
     */
    public function aroundValidate(
        CsrfValidator $subject,
        callable $proceed,
        RequestInterface $request,
        ActionInterface $action
    ) {
        if ($action instanceof \Kevin\Payment\Controller\Payment\Notify) {
            return true;
        }

        return $proceed($request, $action);
    }
}
