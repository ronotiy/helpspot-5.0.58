<?php

namespace HS\Avatar;

class Avatar
{
    var $name = null;
    var $xPerson = null; // The user ID > 0 for staff, 0 (customer), or -1 (system)
    var $historyId = false;
    var $photoId = false;
    var $emoji = false;
    var $isPrivate = false;
    var $size = 48;
    var $domId = '';
    var $backgrounds = [
        '#edf2f7',
        '#e2e8f0',
        '#cbd5e0',
        '#a0aec0',
        '#718096',
        '#fff5f5',
        '#feb2b2',
        '#fc8181',
        '#f56565',
        '#e53e3e',
        '#feebc8',
        '#fbd38d',
        '#f6ad55',
        '#ed8936',
        '#dd6b20',
        '#fefcbf',
        '#faf089',
        '#f6e05e',
        '#ecc94b',
        '#d69e2e',
        '#c6f6d5',
        '#9ae6b4',
        '#68d391',
        '#48bb78',
        '#b2f5ea',
        '#81e6d9',
        '#4fd1c5',
        '#38b2ac',
        '#bee3f8',
        '#90cdf4',
        '#63b3ed',
        '#4299e1',
        '#c3dafe',
        '#a3bffa',
        '#7f9cf5',
        '#667eea',
        '#e9d8fd',
        '#d6bcfa',
        '#b794f4',
        '#9f7aea',
        '#fed7e2',
        '#fbb6ce',
        '#f687b3',
        '#ed64a6',
        '#d53f8c',
    ];

    public function html()
    {
        if($this->xPerson > 0){ // Staff
            if(!empty($this->emoji)){
                $client = new \JoyPixels\Client(new \JoyPixels\Ruleset());

                $ruleset = $client->getRuleset();
                $shortcode_replace = $ruleset->getShortcodeReplace();

                if (!isset($shortcode_replace[$this->emoji])){
                    return $this->renderInitials();
                }

                $filename = static_url().'/static/joypixels/' . $shortcode_replace[$this->emoji][1] . '.svg';

                return $this->renderImgTag($filename);
            }elseif($this->photoId != 0 ){
                return $this->renderImgTag(action('Admin\AdminBaseController@adminFileCalled', ['pg' => 'file.staffphoto', 'id' => $this->photoId]));
            }else{
                return $this->renderInitials();
            }
        }elseif($this->xPerson == -1){ // System
            return $this->renderImgTag(static_url().'/static/img5/'.(inDarkMode() ? 'helpspot-avatar-logo-black' : 'helpspot-avatar').'.svg');
        }else{
            return $this->renderCustomerInitials();
        }
    }

    function renderImgTag($src){
        return '<img src="'.$src.'" class="'.$this->getClassName().'" id="'.$this->domId.'" style="width:'.$this->size.'px;height:'.$this->size.'px;" />';
    }

    function renderInitials(){
        return '<div class="avatar_initials" style="color:#3a2d23;background-color:'.$this->getBg().';width:'.$this->size.'px;height:'.$this->size.'px;font-size:'.($this->size/2).'px;line-height:'.$this->size.'px;" id="'.$this->domId.'">'.$this->initials().'</div>';
    }

    function renderCustomerInitials(){
        return '<div class="avatar_initials" style="background-color:#3a2d23;color:#fff;width:'.$this->size.'px;height:'.$this->size.'px;font-size:'.($this->size/2).'px;line-height:'.$this->size.'px;" id="'.$this->domId.'">'.$this->initials().'</div>';
    }

    function getClassName(){
         if($this->xPerson > 0){ // Staff
            return 'avatar_base avatar_staff';
        }elseif($this->xPerson == -1){ // System
            return 'avatar_base avatar_system';
        }else{
            return 'avatar_base';
        }
    }

    function initials(){
        if(empty($this->name)){
            return '#';
        }

        $names = explode(' ', $this->name);

        if(count($names) > 1){
            $initials = utf8_substr($names[0], 0, 1).utf8_substr($names[1], 0, 1);
        }else{
            $initials = utf8_substr($names[0], 0, 2);
        }

        return utf8_strtoupper($initials);
    }

    function getBg() {

        $number = ord($this->name);
        $i = 1;
        $charLength = strlen($this->name);
        while ($i < $charLength) {
            $number += ord($this->name[$i]);
            $i++;
        }

        return $this->backgrounds[$number % count($this->backgrounds)];
    }

    function size($size){
        $this->size = $size;
        return $this;
    }

    function xPerson($xPerson){
        $this->xPerson = $xPerson;

        if($xPerson > 0){
            $allStaff = apiGetAllUsersComplete();

            $this->name = $allStaff[$xPerson]['fullname'];
            $this->photoId($allStaff[$xPerson]['xPersonPhotoId']);
            $this->emoji($allStaff[$xPerson]['sEmoji']);
        }

        return $this;
    }

    function photoId($id){
        $this->photoId = $id;
        return $this;
    }

    function emoji($emoji){
        $this->emoji = $emoji;
        return $this;
    }

    function name($name){
        $this->name = $name;
        return $this;
    }

    function historyId($historyId){
        $this->historyId = $historyId;
        return $this;
    }

    function isPrivate($isPrivate){
        $this->isPrivate = $isPrivate;
        return $this;
    }

    function domId($domId){
         $this->domId = $domId;
        return $this;
    }
}
