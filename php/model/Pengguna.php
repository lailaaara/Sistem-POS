<?php
declare(strict_types=1);

namespace Resto\Model;

abstract class Pengguna {
    private string $idPengguna;
    private string $nama;
    private string $password;
    private bool $isLoggedIn = false;

    public function __construct(string $idPengguna, string $nama, string $password) {
        $this->idPengguna = $idPengguna;
        $this->nama = $nama;
        $this->password = $password;
    }

    public function login(): bool {
        $this->isLoggedIn = true;
        return true;
    }

    public function logout(): void {
        $this->isLoggedIn = false;
    }

    abstract public function getPeran(): string;

    public function getIdPengguna(): string {
        return $this->idPengguna;
    }

    public function getNama(): string {
        return $this->nama;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function isLoggedIn(): bool {
        return $this->isLoggedIn;
    }
}
