<?php

namespace Kevin\Payment\Plugin\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;

/**
 * Class CsrfValidatorSkip
 * @package Kevin\Payment\Plugin\Framework\App\Request
 */
class CsrfValidatorSkip
{
    /**
     * @param CsrfValidator $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @param ActionInterface $action
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
