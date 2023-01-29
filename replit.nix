{ pkgs }: {
    deps = [
        pkgs.python
        pkgs.nodejs
        pkgs.nodePackages.typescript
        pkgs.ffmpeg
        pkgs.imagemagick
        pkgs.git
    ];
}