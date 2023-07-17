<?php 

    namespace App\Controller;

    use App\Entity\Categories;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    

    #[Route("/categorie", name : "categories_")]

    class CategoriesController extends AbstractController
    {
        #[Route("/", name : "index")]
        public function index() : Response
        {
            return $this->render("categories/index.html.twig");
        }

        #[Route("/{slug}", name : "list")]
        public function details(Categories $category): Response
        {
            // On va chercher tous les produits de la catégorie
            $products = $category->getProducts();
            return $this->render("categories/list.html.twig", compact(
                    'category','products'
                )
            );
            //syntaxe alternative
            /* 
                return $this->render("categories/list.html.twig", [
                        "category" => $category,
                        "products" => $products,
                    ]
                ); 
            */
        }
    }