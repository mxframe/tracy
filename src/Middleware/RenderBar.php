<?php

namespace MxFrame\Tracy\Middleware;

// Laravel
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;

// Symfony
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

// MxFrame
use MxFrame\Tracy\DebuggerManager;
use MxFrame\Tracy\Events\BeforeBarRender;

class RenderBar
{
    /**
     * The debugger Manager.
     *
     * @var DebuggerManager
     */
    protected $debuggerManager;

    /**
     * $events.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * __construct.
     *
     *
     * @param DebuggerManager $debuggerManager
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     */
    public function __construct(DebuggerManager $debuggerManager, Dispatcher $events)
    {
        $this->debuggerManager = $debuggerManager;
        $this->events = $events;
    }

    /**
     * handle.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, $next)
    {
        return $request->has('_tracy_bar') === true
            ? $this->keepFlashSession($request, $next)
            : $this->render($request, $next);
    }

    /**
     * keepFlashSession.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function keepFlashSession($request, $next)
    {
        $type = $request->get('_tracy_bar');
        if ($request->hasSession() === true && in_array($type, ['js', 'css'], true) === false) {
            $request->session()->reflash();
        }

        return $next($request);
    }

    /**
     * render.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function render($request, $next)
    {
        $this->debuggerManager->dispatch();

        $response = $next($request);

        $ajax = $request->ajax();

        if ($this->reject($response, $request, $ajax) === true) {
            return $response;
        }

        $this->events->fire(new BeforeBarRender($request, $response));

        $response->setContent(
            $this->debuggerManager->shutdownHandler(
                $response->getContent(), $ajax
            )
        );

        return $response;
    }

    /**
     * reject.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Illuminate\Http\Request $request
     * @param bool $ajax
     *
     * @return bool
     */
    protected function reject(Response $response, Request $request, $ajax)
    {
        if (
            $response instanceof BinaryFileResponse ||
            $response instanceof StreamedResponse ||
            $response instanceof RedirectResponse
        ) {
            return true;
        }

        if ($ajax === true) {
            return false;
        }

        $contentType = strtolower($response->headers->get('Content-Type'));
        $accepts = $this->debuggerManager->accepts();
        if ((empty($contentType) === true && $response->getStatusCode() >= 400) ||
            count($accepts) === 0
        ) {
            return false;
        }

        foreach ($accepts as $accept) {
            if (strpos($contentType, $accept) !== false) {
                return false;
            }
        }

        return true;
    }
}