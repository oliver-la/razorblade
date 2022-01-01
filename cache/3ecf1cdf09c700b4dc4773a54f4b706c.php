<?php if(!isset($this)) die("This file may not be accessed directly."); ?>
<p>
    Unprocessed: {{ time() }}<br>
    Unprocessed: {!! time() !!}<br>
    Sanitized: <?php $this->e( time() ); ?><br>
    Unsanitized: <?php echo( time() ); ?>
</p>

<?php $content = "test" ?>
<?php
    $fruit = ['banana', 'tomatoes', 'kiwi'];
    $vegetables = ['lettuce', 'spinach'];
    $combined = [
        'fruit' => $fruit,
        'vegetable' => $vegetables
    ];
?>

<?php if($content): ?>
    somethom
<?php endif; ?>

<?php if($content): ?>
    somethom
<?php endif; ?>

<?php $this->addLoop($combined); foreach($combined as $category=>$items): $loop = $this->incrementLoop(); ?>
    <b><?php $this->e( $category ); ?></b><br>
    <ul>
        <?php $this->addLoop($items); foreach($items as $item): $loop = $this->incrementLoop(); ?>
            <li><?php $this->e($item); ?> <?php $this->e($loop->iteration); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endforeach; ?>

<?php $this->placeholderStack['hello'] = function() { ?>
<p>hello from the other side</p>
<?php }; $this->placeholderStack['hello'](); ?>

<p>works?</p>
<?php array_key_exists('hello', $this->placeholderStack) ? $this->placeholderStack['hello']() : $this->e(""); ?>


<?php
    $this->slotStack[] = [];
    $this->componentStack[] = ['view' => 'button', 'args' =>  array (
  'attributes' => 
  array (
    'class' => 'red',
  ),
), 'slot' => function() {
?>hello<?php
    }];
    $__component = array_shift($this->componentStack);
    ob_start();
    $__component['slot']();
    $slot = ob_get_clean();

    $__stack = [];
    foreach(end($this->slotStack) as $__v) {
        ob_start();
        $__v();
        array_push($__stack, ob_get_clean());
    }

    $__args = array_merge($__component['args'], ['slot' => $slot], $__stack);
    $this->view($__component['view'], $__args);
?>
