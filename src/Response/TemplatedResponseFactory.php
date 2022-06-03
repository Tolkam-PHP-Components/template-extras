<?php declare(strict_types=1);

namespace Tolkam\Template\Extras\Response;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Tolkam\Template\RendererInterface;

class TemplatedResponseFactory implements ResponseFactoryInterface
{
    /**
     * @var RendererInterface
     */
    protected RendererInterface $renderer;

    /**
     * @var array
     */
    private array $options = [
        'contentType' => 'text/html;charset=utf-8',
        'errorTemplate' => 'errors::common',
    ];

    /**
     * @param RendererInterface $renderer
     * @param array|null        $options
     */
    public function __construct(RendererInterface $renderer, array $options = null)
    {
        $this->renderer = $renderer;

        if ($options) {
            $this->options = array_replace($this->options, $options);
        }
    }

    /**
     * @inheritDoc
     */
    public function createResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ): ResponseInterface {
        if (!$this->isSuccessCode($code)) {
            return $this->error($code, $reasonPhrase);
        }

        return $this->makeResponse($code, $reasonPhrase);
    }

    /**
     * @param string $templateName
     * @param array  $templateParams
     *
     * @return ResponseInterface
     */
    public function success(string $templateName, array $templateParams = []): ResponseInterface
    {
        return $this->withBody(
            $this->makeResponse(200),
            $templateName,
            $templateParams
        );
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return ResponseInterface
     */
    public function error(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->withBody(
            $this->makeResponse($code, $reasonPhrase),
            (string) $this->options['errorTemplate'],
            ['statusCode' => $code]
        );
    }

    /**
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @return Response|ResponseInterface
     */
    private function makeResponse(int $code, string $reasonPhrase = '')
    {
        return (new Response())
            ->withStatus($code, $reasonPhrase)
            ->withHeader('Content-Type', (string) $this->options['contentType']);
    }

    /**
     * @param ResponseInterface $response
     * @param string            $templateName
     * @param array             $templateParams
     *
     * @return ResponseInterface
     */
    private function withBody(
        ResponseInterface $response,
        string $templateName,
        array $templateParams = []
    ): ResponseInterface {
        $response->getBody()->write(
            $this->renderer->render($templateName, $templateParams)
        );

        return $response;
    }

    /**
     * @param int $code
     *
     * @return bool
     */
    private function isSuccessCode(int $code): bool
    {
        return $code < 305;
    }
}
