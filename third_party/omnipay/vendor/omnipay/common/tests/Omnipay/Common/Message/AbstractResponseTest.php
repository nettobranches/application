<?php

namespace Omnipay\Common\Message;

use Mockery as m;
use Omnipay\Tests\TestCase;

class AbstractResponseTest extends TestCase
{
    /** @var  AbstractResponse */
    protected $response;

    public function setUp()
    {
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponse')->makePartial();
    }

    public function testConstruct()
    {
        $data = array('foo' => 'bar');
        $request = $this->getMockRequest();
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponse', array($request, $data))->makePartial();

        $this->assertSame($request, $this->response->getRequest());
        $this->assertSame($data, $this->response->getData());
    }

    public function testDefaultMethods()
    {
        $this->assertFalse($this->response->isPending());
        $this->assertFalse($this->response->isRedirect());
        $this->assertFalse($this->response->isTransparentRedirect());
        $this->assertFalse($this->response->isCancelled());
        $this->assertNull($this->response->getData());
        $this->assertNull($this->response->getTransactionReference());
        $this->assertNull($this->response->getMessage());
        $this->assertNull($this->response->getCode());
        $this->assertNull($this->response->getRedirectUrl());
        $this->assertEquals('GET', $this->response->getRedirectMethod());
        $this->assertEquals([], $this->response->getRedirectData());
    }

    /**
     * @expectedException \Omnipay\Common\Exception\RuntimeException
     * @expectedExceptionMessage This response does not support redirection.
     */
    public function testGetRedirectResponseNotImplemented()
    {
        $this->response->getRedirectResponse();
    }

    /**
     * @expectedException \Omnipay\Common\Exception\RuntimeException
     * @expectedExceptionMessage This response does not support redirection.
     */
    public function testGetRedirectResponseNotSupported()
    {
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponseTest_MockRedirectResponse')->makePartial();
        $this->response->shouldReceive('isRedirect')->once()->andReturn(false);

        $this->response->getRedirectResponse();
    }

    /**
     * @expectedException \Omnipay\Common\Exception\RuntimeException
     * @expectedExceptionMessage The given redirectUrl cannot be empty.
     */
    public function testGetRedirectResponseUrlNotEmpty()
    {
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponseTest_MockRedirectResponse')->makePartial();
        $this->response->shouldReceive('getRedirectUrl')->once()->andReturn(null);

        $this->response->getRedirectResponse();
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedirect()
    {
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponseTest_MockRedirectResponse')->makePartial();
        $this->response->shouldReceive('getRedirectMethod')->andReturn('GET');

        ob_start();
        $this->response->redirect();
        $body = ob_get_clean();

        $this->assertContains('Redirecting to https://example.com/redirect?a=1&amp;b=2', $body);
    }

    public function testGetRedirectResponseGet()
    {
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponseTest_MockRedirectResponse')->makePartial();
        $this->response->shouldReceive('getRedirectMethod')->andReturn('GET');

        $httpResponse = $this->response->getRedirectResponse();
        $this->assertSame(302, $httpResponse->getStatusCode());
        $this->assertSame('https://example.com/redirect?a=1&b=2', $httpResponse->getTargetUrl());
    }

    public function testGetRedirectResponsePost()
    {
        $data = array('foo' => 'bar', 'key&"' => '<value>');
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponseTest_MockRedirectResponse')->makePartial();
        $this->response->shouldReceive('getRedirectMethod')->andReturn('POST');
        $this->response->shouldReceive('getRedirectData')->andReturn($data);

        $httpResponse = $this->response->getRedirectResponse();
        $this->assertSame(200, $httpResponse->getStatusCode());
        $this->assertContains('<form action="https://example.com/redirect?a=1&amp;b=2" method="post">', $httpResponse->getContent());
        $this->assertContains('<input type="hidden" name="foo" value="bar" />', $httpResponse->getContent());
        $this->assertContains('<input type="hidden" name="key&amp;&quot;" value="&lt;value&gt;" />', $httpResponse->getContent());
    }

    /**
     * @expectedException \Omnipay\Common\Exception\RuntimeException
     * @expectedExceptionMessage Invalid redirect method "DELETE".
     */
    public function testGetRedirectResponseInvalidMethod()
    {
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponseTest_MockRedirectResponse')->makePartial();
        $this->response->shouldReceive('getRedirectMethod')->andReturn('DELETE');

        $this->response->getRedirectResponse();
    }

    public function testGetTransactionIdNull()
    {
        $this->response = m::mock('\Omnipay\Common\Message\AbstractResponseTest_MockRedirectResponse')->makePartial();
        $this->assertNull($this->response->getTransactionId());
    }
}

class AbstractResponseTest_MockRedirectResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isPending()
    {
        return false;
    }

    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return true;
    }

    public function getRedirectUrl()
    {
        return 'https://example.com/redirect?a=1&b=2';
    }

    public function getRedirectMethod() {}
    public function getRedirectData() {}
}
