<!-- End right side container -->
</div>

<div id="footer">

<?php
if ($this->get_page != 'search'):
    //Include search box on bottom of each page
    include $this->loadTemplate('searchbox.tpl.php');
endif;
?>

</div> <!-- End of footer div -->
</div> <!-- End of container div -->

<div id="helpspot-link">
	<?php
    /*
    You may remove this link, however we would be very appreciative if you didn't! Helping spread the word about HelpSpot
    creates a stronger community and a better product for all our customers.
    Sincerely,

    Ian Landsman
    President, UserScape
    ian@userscape.com
    */
    ?>
	
	<strong><?php echo $this->helper->footerCredit(); ?></strong>
</div>
</body>
</html>