<?php

namespace Rector\NetteToSymfony\Tests\Rector\MethodCall\FromHttpRequestGetHeaderToHeadersGetRector\Fixture;

use Symfony\Component\HttpFoundation\Request;

final class SomeController
{
    /**
     * @var \Nette\Http\Request
     */
    private $httpRequest;

    public static function someAction(Request $request)
    {
        $header = $this->httpRequest->getHeader('x');
    }
}

?>
-----
<?php

namespace Rector\NetteToSymfony\Tests\Rector\MethodCall\FromHttpRequestGetHeaderToHeadersGetRector\Fixture;

use Symfony\Component\HttpFoundation\Request;

final class SomeController
{
    /**
     * @var \Nette\Http\Request
     */
    private $httpRequest;

    public static function someAction(Request $request)
    {
        $header = $request->headers->get('x');
    }
}

?>
