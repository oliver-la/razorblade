<?php

class RazorBlade
{
    public $baseDir;
    public $partialsPath = '/partials';
    public $cacheDir = '/cache';
    public $extension = 'php';

    private $buffer;

    private $strayTags = [];

    public $loopStack = [];
    public $placeholderStack = [];
    public $placeholderStackLatestKey = [];

    public $stacksStack = [];
    public $stacksStackOnce = false;

    public $componentStack = [];
    public $slotStack = [];

    public function __construct()
    {
        $this->baseDir = getcwd();
        if (!is_dir($this->baseDir . $this->partialsPath)) {
            mkdir($this->baseDir . $this->partialsPath);
        }
        if (!is_dir($this->baseDir . $this->cacheDir)) {
            mkdir($this->baseDir . $this->cacheDir);
        }
    }

    public function e($input)
    {
        echo htmlentities($input);
    }

    public function echoEscaped($input)
    {
        if (strpos($input[1], '@') === 0) {
            return trim($input[0], '@');
        }

        return "<?php \$this->e({$input[2]}); ?>";
    }

    public function echoRaw($input)
    {
        if (strpos($input[1], '@') === 0) {
            return trim($input[0], '@');
        }

        return "<?php echo({$input[2]}); ?>";
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

    public function parseElement($input)
    {
        /*
            0 => full statement
            1 => element name
            2 => element attributes as space-separated string
        */
        $isClosingTag = strpos($input[1], '/') === 0;

        if ($isClosingTag) {
            return $this->handleEndComponent(null);
        }

        $name = substr($input[1], 2);
        $atts = trim($input[2]);
        if ($atts) {
            preg_match_all('/(\:)?([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/', $atts, $matches, PREG_SET_ORDER);
        } else {
            $matches = [];
        }

        $props = [];
        $attributes = [];

        foreach ($matches as $match) {
            if ($match[1] === ':') {
                $props = array_merge($props, [$match[2] => $match[3]]);
            } else {
                $attributes = array_merge($attributes, [$match[2] => $match[3]]);
            }
        }

        $args = $props;
        $args['attributes'] = $attributes;

        $args = var_export($args, true);

        return $this->handleComponent("'{$name}', {$args}");
    }

    public function parse($content)
    {
        return preg_replace_callback_array([
            // "{{ time() }}" but leave "@{{ time() }}" alone
            '/(@?{{)(.+?)(}})/' => [$this, 'echoEscaped'],
            // "{!! time() !!}" but leave "@{!! time() !!}" alone
            '/(@?{!!)(.+?)(!!})/' => [$this, 'echoRaw'],
            // stolen from BladeOne project, since this is way over the top of my head.
            // Ignore any statements that start with two @@, equivalent to wrapping all single-@ statements in a @verbatim block.
            '/\B(?<!@)@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x' => [$this, 'parseStatement'],
            // <x-component></x-component>
            '/<(\/?x\-.*?)(?![\w-])([^\>\/]*(?:\/(?!\>)[^\>\/]*)*?)(?:(\/)\>|\>(?:([^\<]*+(?:\<(?!\/\2\>)[^\<]*+)*+)<\/\2\>)?)/' => [$this, 'parseElement']
        ], $content);
    }

    public function render($view, $silent = false)
    {
        $relativePath = str_replace('.', '/', $view);
        $absolutePath = $this->baseDir . '/' . $relativePath . '.' . $this->extension;

        if (!file_exists($absolutePath)) {
            $absolutePath = $this->baseDir . $this->partialsPath . '/' . $relativePath . '.' . $this->extension;

            if (!file_exists($absolutePath)) {
                if ($silent) {
                    return;
                }
                throw new Exception('View not found');
            }
        }

        $this->buffer = file_get_contents($absolutePath);

        $this->buffer = $this->parse($this->buffer);

        if (count($this->strayTags)) {
            throw new Exception('Not all statements are terminated in view.');
        }

        $checksum = crc32($view);
        $outputPath = $this->baseDir . $this->cacheDir . "/$checksum.php";
        file_put_contents($outputPath, $this->buffer);

        return $outputPath;
    }

    public function view($view, $args = [], $silent = false)
    {
        $__view = $this->render($view, $silent);
        if ($__view) {
            if (array_key_exists('attributes', $args)) {
                $args['attributes'] = new RazorBladeAttributeBag($args['attributes']);
            }
            extract($args);
            require "{$__view}";
        }
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

    public function handleIf($__args)
    {
        $this->registerStrayTag('if');
        return "<?php if({$__args}): ?>";
    }

    public function handleElseIf($__args)
    {
        return "<?php elseif({$__args}): ?>";
    }

    public function handleElse($__args)
    {
        return "<?php else: ?>";
    }

    public function handleEndIf($__args)
    {
        $this->unregisterStrayTag('if');
        return "<?php endif; ?>";
    }

    public function handleUnless($__args)
    {
        $this->registerStrayTag('unless');
        return "<?php if(!({$__args})): ?>";
    }

    public function handleEndUnless($__args)
    {
        $this->unregisterStrayTag('unless');
        return "<?php endif; ?>";
    }

    public function handleIsset($__args)
    {
        $this->registerStrayTag('isset');
        return "<?php if(isset({$__args})): ?>";
    }

    public function handleEndIsset($__args)
    {
        $this->unregisterStrayTag('isset');
        return "<?php endif; ?>";
    }

    public function handleEmpty($__args)
    {
        $this->registerStrayTag('empty');
        return "<?php if(empty({$__args})): ?>";
    }

    public function handleEndEmpty($__args)
    {
        $this->unregisterStrayTag('empty');
        return "<?php endif; ?>";
    }

    public function handleSwitch($__args)
    {
        $this->registerStrayTag('switch');
        $this->registerStrayTag('default');
        return "<?php switch({$__args}): ?>";
    }

    public function handleCase($__args)
    {
        return "<?php case {$__args}: ?>";
    }

    public function handleBreak($__args)
    {
        if ($__args) {
            return "<?php if({$__args}) break; ?>";
        }
        return "<?php break; ?>";
    }

    public function handleContinue($__args)
    {
        if ($__args) {
            return "<?php if({$__args}) continue; ?>";
        }
        return "<?php continue; ?>";
    }

    public function handleDefault($__args)
    {
        $this->unregisterStrayTag('default');
        return "<?php default: ?>";
    }

    public function handleEndSwitch($__args)
    {
        $this->unregisterStrayTag('switch');
        return "<?php endswitch: ?>";
    }

    public function addLoop($data)
    {
        $count = is_countable($data) ? count($data) : null;

        $newLoop = new stdClass;
        $newLoop->index = -1;
        $newLoop->iteration = 0;
        $newLoop->remaining = $count;
        $newLoop->first = true;
        $newLoop->last = $count === 1;
        $newLoop->even = false;
        $newLoop->odd = true;
        $newLoop->depth = count($this->loopStack) + 1;
        $newLoop->parent = $newLoop->depth === 1 ? null : $this->loopStack[$newLoop->depth - 2];

        $this->loopStack[] = $newLoop;
    }

    public function incrementLoop()
    {
        $loop = &$this->loopStack[count($this->loopStack) - 1];
        $loop->index++;
        $loop->iteration++;
        $loop->remaining--;
        $loop->first = $loop->index === 0;
        $loop->last = $loop->remaining === 0;
        $loop->even = $loop->iteration % 2 === 0;
        $loop->odd = !$loop->even;

        return $loop;
    }

    public function handleFor($__args)
    {
        $this->registerStrayTag('for');
        return <<<EOS
        <?php
            \$loop = [];
            for({$__args}) {
                \$loop[] = null;
            }
            \$this->addLoop(\$loop);
            for({$__args}):
                \$loop = \$this->incrementLoop();
        ?>
        EOS;
    }

    public function handleEndFor($__args)
    {
        $this->unregisterStrayTag('for');
        array_shift($this->loopStack);
        return "<?php endfor; ?>";
    }

    public function handleForeach($__args)
    {
        $this->registerStrayTag('foreach');
        $iterable = trim(explode('as', $__args)[0]);
        return "<?php \$this->addLoop({$iterable}); foreach({$__args}): \$loop = \$this->incrementLoop(); ?>";
    }

    public function handleEndForeach($__args)
    {
        $this->unregisterStrayTag('foreach');
        array_shift($this->loopStack);
        return "<?php endforeach; ?>";
    }

    public function handleWhile($__args)
    {
        $this->registerStrayTag('while');
        // ``while`` is a bit tricky, as for example in wordpress it would lead to an endless loop if we were to use the foreach approach
        // loop properties "remaining" and "last" are unavailable here.
        return <<<EOS
        <?php
            \$this->addLoop(null);
            while({$__args}):
                \$loop = \$this->incrementLoop();
        ?>
        EOS;
        return "<?php while({$__args}): ?>";
    }

    public function handleEndWhile($__args)
    {
        $this->unregisterStrayTag('while');
        array_shift($this->loopStack);
        return "<?php endwhile; ?>";
    }

    public function handleClass($__args)
    {
        $code = <<<EOS
        <?php
            \$__mapped = array_map(function(\$__k, \$__v) {
                if(is_numeric(\$__k)) { return \$__v; }
                if(\$__v) { return \$__k; }
            }, {$__args});
            $this->e(implode(' ', \$__mapped));
        ?>
        EOS;

        return preg_replace('/\r?\n/', '', $code);
    }

    private function trimQuotes($input)
    {
        return trim($input, '\'"');
    }

    public function handleSection($__args)
    {
        $this->registerStrayTag('section');
        $this->placeholderStackLatestKey = $__args;
        return "<?php \$this->placeholderStack[$__args] = function() { ?>";
    }

    public function handleEndSection($__args)
    {
        $this->unregisterStrayTag('section');
        return "<?php }; ?>";
    }

    public function handleShow($__args)
    {
        $this->unregisterStrayTag('section');
        $__args = $this->placeholderStackLatestKey;
        return "<?php }; \$this->placeholderStack[{$__args}](); ?>";
    }

    public function handleYield($__args)
    {
        $__args = explode(',', $__args);
        $__default = count($__args) >= 2 ? $__args[1] : '""';

        return "<?php array_key_exists({$__args[0]}, \$this->placeholderStack) ? \$this->placeholderStack[{$__args[0]}]() : \$this->e($__default); ?>";
    }

    public function handlePhp($__args)
    {
        if ($__args) {
            return "<?php $__args ?>";
        }

        return "<?php";
    }

    public function handleEndPhp($__args)
    {
        return "?>";
    }

    public function handleInclude($__args)
    {
        return "<?php \$this->view($__args); ?>";
    }

    public function handleIncludeIf($__args)
    {
        return "<?php \$this->view($__args, true); ?>";
    }

    public function handleIncludeWhen($__args)
    {
        $__args = explode(',', $__args);
        $__boolean = array_pop($__args);
        $__args = implode(',', $__args);
        return "<?php if({$__boolean}) { \$this->view({$__args}); } ?>";
    }

    public function handleIncludeUnless($__args)
    {
        $__args = explode(',', $__args);
        $__boolean = array_pop($__args);
        $__args = implode(',', $__args);
        return "<?php if(!({$__boolean})) { \$this->view({$__args}); } ?>";
    }

    public function handleIncludeFirst($__args)
    {
        $__args = explode(',', $__args);
        $__views = trim(array_pop($__args), " \t\n\r\0\x0B[]");
        $__views = explode(',', $__views);
        $__args = implode(',', $__args);

        foreach ($__views as $__view) {
            if (is_string($this->render($this->trimQuotes($__view), true))) {
                break;
            }
        }

        return "<?php \$this->view($__view, $__args); ?>";
    }

    public function handleEach($__args)
    {
        $__args = explode(',', $__args);
        $__view = array_pop($__args);
        $__iterable = array_pop($__args);
        $__var = $this->trimQuotes(array_pop($__args));
        $__view_empty = "''";
        if (isset($__args[0])) {
            $__view_empty = $__args[0];
        }
        return <<<EOS
        <?php
            if(count({$__iterable})):
                \$this->addLoop({$__iterable});
                foreach({$__iterable} as \$$__var):
                    \$loop = \$this->incrementLoop();
                    \$this->view($__view);
            else:
                if({$__view_empty} !== ''):
                    \$this->view($__view_empty);
                endif;
            endif;
        ?>
        EOS;
    }

    public function handleStack($__args)
    {
        return "<?php foreach(\$this->stacksStack[$__args] as \$__stack) { \$__stack(); } ?>";
    }

    public function handlePush($__args)
    {
        if (!$this->stacksStackOnce) {
            $this->registerStrayTag('push');
            return "<?php if(!array_key_exists($__args, \$this->stacksStack)) { \$this->stacksStack[$__args] = []; } \$this->stacksStack[$__args][] = function() { ?>";
        }
    }

    public function handleEndPush($__args)
    {
        if (!$this->stacksStackOnce) {
            $this->unregisterStrayTag('push');
            return "<?php }; ?>";
        }
    }

    public function handlePrepend($__args)
    {
        if (!$this->stacksStackOnce) {
            $this->registerStrayTag('prepend');
            return "<?php if(!array_key_exists($__args, \$this->stacksStack)) { \$this->stacksStack[$__args] = []; } array_unshift(\$this->stacksStack[$__args], function() { ?>";
        }
    }

    public function handleEndPrepend($__args)
    {
        if (!$this->stacksStackOnce) {
            $this->unregisterStrayTag('prepend');
            return "<?php }); ?>";
        }
    }

    public function handleOnce($__args)
    {
        $this->stacksStackOnce = true;
        return '';
    }

    public function handleEndOnce($__args)
    {
        $this->stacksStackOnce = false;
        return '';
    }

    public function handleExtends($__args)
    {
        return $this->handleInclude($__args);
    }

    public function handleComponent($__args)
    {
        $__args = explode(',', $__args);
        $__view = array_shift($__args);
        $__args = implode(',', $__args) ?: '[]';
        $this->slotStack[] = [];
        return <<<EOS
        <?php
            \$this->componentStack[] = ['view' => {$__view}, 'args' => {$__args}, 'slot' => function() {
        ?>
        EOS;
    }

    public function handleEndComponent($__args)
    {
        return <<<EOS
        <?php
            }];
            \$__component = array_shift(\$this->componentStack);
            ob_start();
            \$__component['slot']();
            \$slot = ob_get_clean();

            \$__stack = [];
            foreach(end(\$this->slotStack) as \$__v) {
                ob_start();
                \$__v();
                array_push(\$__stack, ob_get_clean());
            }

            \$__args = array_merge(\$__component['args'], ['slot' => \$slot], \$__stack);
            \$this->view(\$__component['view'], \$__args);
        ?>
        EOS;
    }

    public function handleSlot($__args)
    {
        return "<?php end(\$this->slotStack)[{$__args}] = function() { ?>";
    }

    public function handleEndSlot($__args)
    {
        return "<?php }; ?>";
    }
}

class RazorBladeAttributeBag
{
    public $attributes;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __toString()
    {
        return $this->merge([]);
    }

    public function merge($with)
    {
        $source = $this->attributes;

        foreach ($with as $key=>$value) {
            if (array_key_exists($key, $source)) {
                $source[$key] .= ' ' . $value;
            } else {
                $source[$key] = $value;
            }
        }

        $map = [];
        foreach ($source as $key=>$value) {
            if (!$value) {
                $map[] = $key;
            }
            $map[] = $key . '="' . $value . '"';
        }

        return implode(' ', $map);
    }
}
