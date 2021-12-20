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
