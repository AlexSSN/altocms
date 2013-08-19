<?php
/*---------------------------------------------------------------------------
 * @Project: Alto CMS
 * @Project URI: http://altocms.com
 * @Description: Advanced Community Engine
 * @Copyright: Alto CMS Team
 * @License: GNU GPL v2 & MIT
 *----------------------------------------------------------------------------
 */

class ModuleMresource_EntityMresource extends Entity {

    /**
     * If resource external link
     *
     * @return bool
     */
    public function IsLink() {

        return (bool)$this->GetLink();
    }

    public function IsFile() {

        return !$this->IsLink() && $this->GetHashFile();
    }

    public function CanDelete() {

        return (bool)$this->getProp('candelete');
    }


    /**
     * Sets full url of resource
     *
     * @param $sUrl
     */
    public function SetUrl($sUrl) {

        if (substr($sUrl, 0, 1) === '@') {
            $sPathUrl = substr($sUrl, 1);
            $sUrl = F::File_RootUrl() . $sPathUrl;
        } else {
            $sPathUrl = F::File_LocalUrl($sUrl);
        }
        if ($sPathUrl) {
            // Сохраняем относительный путь
            $this->SetPathUrl('@' . $sPathUrl);
            if (!$this->getPathFile()) {
                $this->SetFile(F::File_Url2Dir($sUrl));
            }
        } else {
            // Сохраняем абсолютный путь
            $this->SetPathUrl($sUrl);
        }
        if (is_null($this->GetPathFile())) {
            if (is_null($this->GetLink())) {
                $this->SetLink(true);
            }
            if (is_null($this->GetType())) {
                $this->SetType(ModuleMresource::TYPE_HREF);
            }
        }
        $this->RecalcHash();
    }

    /**
     * Sets full dir path of resource
     *
     * @param $sFile
     */
    public function SetFile($sFile) {

        if ($sFile) {
            if ($sPathDir = F::File_LocalDir($sFile)) {
                // Сохраняем относительный путь
                $this->SetPathFile('@' . $sPathDir);
                if (!$this->GetPathUrl()) {
                    $this->SetUrl(F::File_Dir2Url($sFile));
                }
            } else {
                // Сохраняем абсолютный путь
                $this->SetPathFile($sFile);
            }
            $this->SetLink(false);
        } else {
            $this->SetPathFile(null);
        }
        $this->RecalcHash();
    }

    /**
     * Returns ID of media resource
     *
     * @return mixed|null
     */
    public function GetId() {

        return $this->getProp('mresource_id');
    }

    /**
     * Returns full url to media resource
     *
     * @return mixed
     */
    public function GetUrl() {

        $sUrl = $this->GetPathUrl();
        if (substr($sUrl, 0, 1) == '@') {
            $sUrl = F::File_NormPath(F::File_RootUrl() . '/' . substr($sUrl, 1));
        }
        return $sUrl;
    }

    /**
     * Returns full dir path to media resource
     *
     * @return mixed
     */
    public function GetFile() {

        $sPathFile = $this->GetPathFile();
        if (substr($sPathFile, 0, 1) == '@') {
            $sPathFile = F::File_NormPath(F::File_RootDir() . '/' . substr($sPathFile, 1));
        }
        return $sPathFile;
    }

    /**
     * Recalc both hashs (url & dir)
     */
    public function RecalcHash() {

        if (($sFile = $this->GetFile()) && F::File_Exists($sFile)) {
            $sHashFile = md5_file($sFile);
        } else {
            $sHashFile = null;
        }
        if ($sPathUrl = $this->GetPathUrl()) {
            $sHashUrl = md5($sPathUrl);
        } else {
            $sHashUrl = null;
        }
        $this->SetHashUrl($sHashUrl);
        $this->SetHashFile($sHashFile);
    }

    public function GetHash() {

        return $this->GetHashUrl();
    }
}

// EOF