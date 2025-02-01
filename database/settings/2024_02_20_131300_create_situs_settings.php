<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('situs.brand_name', 'Tes Calon Mubaligh');
        $this->migrator->add('situs.brand_logo', 'sites/logo.png');
        $this->migrator->add('situs.brand_logoHeight', '3rem');
        $this->migrator->add('situs.site_active', true);
        $this->migrator->add('situs.site_favicon', 'sites/favico.ico');
        $this->migrator->add('situs.site_theme', [
            "primary" => "#3150AE",
            "secondary" => "#3be5e8",
            "gray" => "#485173",
            "success" => "#1DCB8A",
            "danger" => "#ff5467",
            "info" => "#6E6DD7",
            "warning" => "#f5de8d",
        ]);
    }
};
