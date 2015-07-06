<li>
    <div><a href="<?php printf('admin.php?id=%s', $product['id']) ?>"><img width="150" height="150" src="<?php echo get_img_url($product['img']) /*printf('media/thumbnail/sample%s.jpg', rand(1,4))*/ ?>"></a></div>
    <div><?php printf('ID: %s', $product['id']) ?></div>
    <div><?php printf('Name: %s', $product['name']) ?></div>
    <div class="wr-div"><?php printf('Description: %s', $product['description']) ?></div>
    <div><?php printf('Price: %s', $product['price']) ?></div>
</li>

