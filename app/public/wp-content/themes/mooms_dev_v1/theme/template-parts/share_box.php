<?php
use SocialLinks\Page;

$page = new Page([
    'url'   => get_the_permalink(),
    'title' => get_the_title(),
    'text'  => get_the_excerpt(),
    'image' => get_the_post_thumbnail_url('full'),
]);
?>
<div class="share-post">
    <div class="inner">
        <div class="social-share">
            <ul>
                <li class="facebook">
                    <a href="javascript:"
                       onclick="window.open('<?php echo $page->facebook->shareUrl ?>','Share post','width=600,height=600,top=150,left=250'); return false;"><i class="fab fa-facebook-f"></i></a>
                </li>
                <li class="twitter">
                    <a href="javascript:"
                       onclick="window.open('<?php echo $page->twitter->shareUrl ?>','Share post','width=600,height=600,top=150,left=250'); return false;"><i class="fab fa-twitter"></i></a>
                </li>
                <li class="linkedin">
                    <a href="javascript:"
                       onclick="window.open('<?php echo $page->linkedin->shareUrl ?>','Share post','width=600,height=600,top=150,left=250'); return false;"><i class="fab fa-linkedin-in"></i></a>
                </li>
                <li class="pinterest">
                    <a href="javascript:"
                       onclick="window.open('<?php echo $page->pinterest->shareUrl ?>','Share post','width=600,height=600,top=150,left=250'); return false;"><i class="fab fa-pinterest-p"></i></a>
                </li>
            </ul>
        </div>
    </div>

</div>

