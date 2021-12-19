<?php

class RazorBlade
{
    public $baseDir;
    public $partialsPath = '/partials';
    public $extension = 'php';

    private $buffer;

    private $strayTags = [];

    public function __construct()
    {
        $this->baseDir = getcwd();
    }

    public function echoEscaped($input)
    {
        $sanitized = htmlentities(trim($input[2]));
        return "<?php echo {$sanitized}; ?>";
    }

    public function echoRaw($input)
    {
        $sanitized = trim($input[2]);
        return "<?php echo {$sanitized}; ?>";
    }

    public function parseStatement($input)
    {
        /*
            0 => full statement
            1 => statement name without @ prefix
            2 => ???
            3 => arguments with parenthesis
            4 => arguments without parenthesis
        */
        if (is_callable([$this, 'handle' . $input[1]])) {
            return $this->{'handle' . $input[1]}($input[4] ?? null);
        }

        // return as-is
        return $input[0];
    }

    public function parse($content)
    {
        return preg_replace_callback_array([
            // "{{ time() }}" but leave "@{{ time() }}" alone
            '/(?<!@)({{)(.+)(}})/' => [$this, 'echoEscaped'],
            // "{!! time() !!}" but leave "@{!! time() !!}" alone
            '/(?<!@)({!!)(.+)(!!})/' => [$this, 'echoRaw'],
            // stolen from BladeOne project, since this is way over the top of my head.
            // Ignore any statements that start with two @@, equivalent to wrapping all single-@ statements in a @verbatim block.
            '/\B(?<!@)@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x' => [$this, 'parseStatement']
        ], $content);
    }

    public function render($view)
    {
        $relativePath = str_replace('.', '/', $view);
        $absolutePath = $this->baseDir . '/' . $relativePath . '.' . $this->extension;

        if (!file_exists($absolutePath)) {
            $absolutePath = $this->baseDir . $this->partialsPath . '/' . $relativePath . '.' . $this->extension;

            if (!file_exists($absolutePath)) {
                throw new Exception('View not found');
            }
        }

        ob_start();
        include($absolutePath);
        $this->buffer = ob_get_clean();

        $this->buffer = $this->parse($this->buffer);

        if (count($this->strayTags)) {
            throw new Exception('Not all statements are terminated in view.');
        }

        file_put_contents('cache.php', $this->buffer);
        require 'cache.php';
    }

    private function registerStrayTag($tag)
    {
        $this->strayTags[] = $tag;
    }

    private function unregisterStrayTag($tag)
    {
        if (($key = array_search($tag, $this->strayTags)) !== false) {
            unset($this->strayTags[$key]);
        }
    }

    public function handleIf($args)
    {
        $this->registerStrayTag('if');
        return "<?php if({$args}): ?>";
    }

    public function handleEndIf($args)
    {
        $this->unregisterStrayTag('if');
        return "<?php endif; ?>";
    }
}
