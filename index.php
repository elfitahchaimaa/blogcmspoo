<?php

class User {
    public int $id;
    public string $username;
    public string $password;
    public string $role;

    public function __construct(int $id, string $username, string $password, string $role) {
        $this->id = $id;
        $this->username = $username;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->role = $role;
    }
}

class Article {
    public int $id;
    public string $title;
    public string $content;
    public User $author;
    public string $status;

    public function __construct(int $id, string $title, string $content, User $author) {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->author = $author;
        $this->status = 'draft';
    }
}

class Comment {
    public int $id;
    public string $content;
    public User $author;
    public Article $article;

    public function __construct(int $id, string $content, User $author, Article $article) {
        $this->id = $id;
        $this->content = $content;
        $this->author = $author;
        $this->article = $article;
    }
}

class BlogCMS {
    public array $users = [];
    public array $articles = [];
    public array $comments = [];
    public ?User $currentUser = null;

    private int $articleId = 1;
    private int $commentId = 1;

    public function seed() {
        $this->users[] = new User(1, 'admin', 'admin', 'admin');
        $this->users[] = new User(2, 'editeur', '123', 'editor');
        $this->users[] = new User(3, 'auteur', '123', 'author');
        $this->users[] = new User(4, 'visiteur', '123', 'visitor');
    }

    public function login(string $username, string $password): bool {
        foreach ($this->users as $user) {
            if ($user->username === trim($username) && password_verify($password, $user->password)) {
                $this->currentUser = $user;
                return true;
            }
        }
        return false;
    }

    public function logout() {
        $this->currentUser = null;
    }

    public function createArticle() {
        echo "Titre : ";
        $title = trim(fgets(STDIN));
        echo "Contenu : ";
        $content = trim(fgets(STDIN));

        $this->articles[] = new Article(
            $this->articleId++,
            $title,
            $content,
            $this->currentUser
        );

        echo "Article créé\n";
    }

    public function listDraftArticles() {
        foreach ($this->articles as $a) {
            if ($a->status === 'draft') {
                echo "[{$a->id}] {$a->title} - {$a->author->username}\n";
            }
        }
    }

    public function publishArticle() {
        echo "ID article : ";
        $id = (int) trim(fgets(STDIN));

        foreach ($this->articles as $a) {
            if ($a->id === $id && $a->status === 'draft') {
                $a->status = 'published';
                echo "Article publié\n";
                return;
            }
        }
        echo "Article introuvable\n";
    }

    public function listPublishedArticles() {
        $found = false;

        foreach ($this->articles as $a) {
            if ($a->status === 'published') {
                echo "\n[{$a->id}] {$a->title} - {$a->author->username}\n";
                echo "{$a->content}\n";

                foreach ($this->comments as $c) {
                    if ($c->article === $a) {
                        echo " - {$c->author->username} : {$c->content}\n";
                    }
                }
                $found = true;
            }
        }

        if (!$found) {
            echo "Aucun article publié\n";
        }
    }

    public function addComment() {
        echo "ID article : ";
        $id = (int) trim(fgets(STDIN));

        foreach ($this->articles as $article) {
            if ($article->id === $id && $article->status === 'published') {
                echo "Commentaire : ";
                $content = trim(fgets(STDIN));

                $this->comments[] = new Comment(
                    $this->commentId++,
                    $content,
                    $this->currentUser,
                    $article
                );

                echo "Commentaire ajouté\n";
                return;
            }
        }
        echo "Article introuvable ou non publié\n";
    }

    public function menuGuest() {
        echo "\n===== MENU =====\n";
        echo "1. Connexion\n";
        echo "2. Lire articles\n";
        echo "0. Quitter\n";
        echo "Choix : ";
    }

    public function menuUser() {
        echo "\n===== MENU =====\n";
        echo "Connecté : {$this->currentUser->username} ({$this->currentUser->role})\n";
        echo "1. Lire articles\n";
        echo "2. Déconnexion\n";

        if ($this->currentUser->role === 'author') {
            echo "3. Créer article\n";
        }

        if (in_array($this->currentUser->role, ['editor', 'admin'])) {
            echo "4. Articles à publier\n";
            echo "5. Publier article\n";
        }

        echo "6. Ajouter un commentaire\n";
        echo "0. Quitter\n";
        echo "Choix : ";
    }
}

$cms = new BlogCMS();
$cms->seed();

while (true) {
    if ($cms->currentUser === null) {
        $cms->menuGuest();
        $choice = trim(fgets(STDIN));

        if ($choice == 1) {
            echo "Username : ";
            $u = trim(fgets(STDIN));
            echo "Password : ";
            $p = trim(fgets(STDIN));
            echo $cms->login($u, $p) ? "Connexion OK\n" : "Erreur\n";
        } elseif ($choice == 2) {
            $cms->listPublishedArticles();
        } elseif ($choice == 0) {
            break;
        }
    } else {
        $cms->menuUser();
        $choice = trim(fgets(STDIN));

        if ($choice == 1) $cms->listPublishedArticles();
        elseif ($choice == 2) { $cms->logout(); echo "Déconnecté\n"; }
        elseif ($choice == 3 && $cms->currentUser->role === 'author') $cms->createArticle();
        elseif ($choice == 4 && in_array($cms->currentUser->role, ['editor','admin'])) $cms->listDraftArticles();
        elseif ($choice == 5 && in_array($cms->currentUser->role, ['editor','admin'])) $cms->publishArticle();
        elseif ($choice == 6) $cms->addComment();
        elseif ($choice == 0) break;
    }
}
