<?php

switch ($this->get_page) {
    case 'home':
        include $this->loadTemplate('home.tpl.php');

        break;
    case 'request':
        include $this->loadTemplate('request.tpl.php');

        break;
    case 'request.check':
        // If a visitor is logged in already send them to their history
        if ($this->splugin('Request_Check', 'isLoggedIn') && empty($this->get_id)) {
            $this->helper->redirect($this->cf_url.'/index.php?pg=request.history');
        }

        include $this->loadTemplate('request.check.tpl.php');

        break;
    case 'request.history':
        include $this->loadTemplate('request.history.tpl.php');

        break;
    case 'css':
        include $this->loadTemplate('css.tpl.php');

        break;
    case 'css.grey':
        include $this->loadTemplate('css.grey.tpl.php');

        break;
    case 'css.blue':
        include $this->loadTemplate('css.blue.tpl.php');

        break;
    case 'css.clean':
        include $this->loadTemplate('css.clean.tpl.php');

        break;
    case 'ie.css':
        include $this->loadTemplate('ie.css.tpl.php');

        break;
    case 'ie.css.grey':
        include $this->loadTemplate('ie.css.grey.tpl.php');

        break;
    case 'ie.css.blue':
        include $this->loadTemplate('ie.css.blue.tpl.php');

        break;
    case 'js':
        include $this->loadTemplate('js.tpl.php');

        break;
    case 'kb':
        include $this->loadTemplate('kb.tpl.php');

        break;
    case 'kb.book':
        if (! $this->get_id) {
            $this->helper->redirect($this->cf_url);
        } // ID Required for this template
        include $this->loadTemplate('kb.book.tpl.php');

        break;
    case 'kb.chapter':
        if (! $this->get_id) {
            $this->helper->redirect($this->cf_url);
        } // ID Required for this template
        include $this->loadTemplate('kb.chapter.tpl.php');

        break;
    case 'kb.page':
        if (! $this->get_id) {
            $this->helper->redirect($this->cf_url);
        } // ID Required for this template
        include $this->loadTemplate('kb.page.tpl.php');

        break;
    case 'kb.printer.friendly':
        if (! $this->get_id) {
            $this->helper->redirect($this->cf_url);
        } // ID Required for this template
        include $this->loadTemplate('kb.printer.friendly.tpl.php');

        break;
    case 'search':
        include $this->loadTemplate('search.tpl.php');

        break;
    case 'tag.search':
        include $this->loadTemplate('tag.search.tpl.php');

        break;
    case 'login.create':
        include $this->loadTemplate('login.create.tpl.php');
        break;
    case 'login.reset':
        include $this->loadTemplate('login.reset.tpl.php');
        break;
    case 'login.forgot':
        include $this->loadTemplate('login.forgot.tpl.php');
        break;
    case 'maintenance':
        include $this->loadTemplate('maintenance.tpl.php');
        break;
    default:
        $this->helper->redirect($this->cf_url);

        break;
}
