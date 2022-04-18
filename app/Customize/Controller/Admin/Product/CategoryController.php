<?php

namespace Customize\Controller\Admin\Product;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Category;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Service\CsvExportService;
use Eccube\Util\CacheUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Customize\Repository\CategoryRepository;
use Eccube\Form\Type\Admin\CategoryType;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Filesystem\Filesystem;

class CategoryController extends AbstractController
{

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * CategoryController constructor.
     *
     * @param CsvExportService $csvExportService
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CsvExportService $csvExportService,
        CategoryRepository $categoryRepository
    ) {
        $this->csvExportService = $csvExportService;
        $this->categoryRepository = $categoryRepository;
    }
    /**
     * @Route("/%eccube_admin_route%/product/category", name="admin_product_category", methods={"GET", "POST"})
     * @Route("/%eccube_admin_route%/product/category/{parent_id}", requirements={"parent_id" = "\d+"}, name="admin_product_category_show", methods={"GET", "POST"})
     * @Route("/%eccube_admin_route%/product/category/{id}/edit", requirements={"id" = "\d+"}, name="admin_product_category_edit", methods={"GET", "POST"})
     * @Template("@admin/Product/category.twig")
     */
    public function index(Request $request, $parent_id = null, $id = null, CacheUtil $cacheUtil)

    {
        if ($parent_id) {
            /** @var Category $Parent */
            $Parent = $this->categoryRepository->find($parent_id);
            if (!$Parent) {
                throw new NotFoundHttpException();
            }
        } else {
            $Parent = null;
        }
        if ($id) {
            $TargetCategory = $this->categoryRepository->find($id);
            if (!$TargetCategory) {
                throw new NotFoundHttpException();
            }
            $Parent = $TargetCategory->getParent();
        } else {
            $TargetCategory = new \Eccube\Entity\Category();
            $TargetCategory->setParent($Parent);
            if ($Parent) {
                $TargetCategory->setHierarchy($Parent->getHierarchy() + 1);
            } else {
                $TargetCategory->setHierarchy(1);
            }
        }

        $Categories = $this->categoryRepository->getList($Parent);

        // ツリー表示のため、ルートからのカテゴリを取得
        $TopCategories = $this->categoryRepository->getList(null);

        $builder = $this->formFactory
            ->createBuilder(CategoryType::class, $TargetCategory);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Parent' => $Parent,
                'TargetCategory' => $TargetCategory,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_CATEGORY_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $forms = [];
        foreach ($Categories as $Category) {
            $forms[$Category->getId()] = $this->formFactory
                ->createNamed('category_' . $Category->getId(), CategoryType::class, $Category);
        }

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if ($this->eccubeConfig['eccube_category_nest_level'] < $TargetCategory->getHierarchy()) {
                    throw new BadRequestHttpException();
                }
                log_info('カテゴリ登録開始', [$id]);
                // ファイルアップロード
                $file = $form['category_image']->getData();
                $fs = new Filesystem();
                if ($file && $fs->exists($this->getParameter('eccube_temp_image_dir') . '/' . $file)) {
                    $fs->rename(
                        $this->getParameter('eccube_temp_image_dir') . '/' . $file,
                        $this->getParameter('eccube_save_image_dir') . '/' . $file
                    );
                }
                $this->categoryRepository->save($TargetCategory);

                log_info('カテゴリ登録完了', [$id]);

                // $formが保存されたフォーム
                // 下の編集用フォームの場合とイベント名が共通のため
                // このイベントのリスナーではsubmitされているフォームを判定する必要がある
                $event = new EventArgs(
                    [
                        'form' => $form,
                        'Parent' => $Parent,
                        'TargetCategory' => $TargetCategory,
                    ],
                    $request
                );
                $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_CATEGORY_INDEX_COMPLETE, $event);

                $this->addSuccess('admin.common.save_complete', 'admin');

                $cacheUtil->clearDoctrineCache();

                if ($Parent) {
                    return $this->redirectToRoute('admin_product_category_show', ['parent_id' => $Parent->getId()]);
                } else {
                    return $this->redirectToRoute('admin_product_category');
                }
            }

            foreach ($forms as $editForm) {
                $editForm->handleRequest($request);
                if ($editForm->isSubmitted() && $editForm->isValid()) {
                    // ファイルアップロード
                    $file = $editForm['category_image']->getData();
                    $fs = new Filesystem();
                    if ($file && $fs->exists($this->getParameter('eccube_temp_image_dir') . '/' . $file)) {
                        $fs->rename(
                            $this->getParameter('eccube_temp_image_dir') . '/' . $file,
                            $this->getParameter('eccube_save_image_dir') . '/' . $file
                        );
                    }
                    $this->categoryRepository->save($editForm->getData());

                    // $editFormが保存されたフォーム
                    // 上の新規登録用フォームの場合とイベント名が共通のため
                    // このイベントのリスナーではsubmitされているフォームを判定する必要がある
                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'editForm' => $editForm,
                            'Parent' => $Parent,
                            'TargetCategory' => $editForm->getData(),
                        ],
                        $request
                    );

                    $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_CATEGORY_INDEX_COMPLETE, $event);

                    $this->addSuccess('admin.common.save_complete', 'admin');

                    $cacheUtil->clearDoctrineCache();

                    if ($Parent) {
                        return $this->redirectToRoute('admin_product_category_show', ['parent_id' => $Parent->getId()]);
                    } else {
                        return $this->redirectToRoute('admin_product_category');
                    }
                }
            }
        }

        $formViews = [];
        foreach ($forms as $key => $value) {
            $formViews[$key] = $value->createView();
        }

        $Ids = [];
        if ($Parent && $Parent->getParents()) {
            foreach ($Parent->getParents() as $item) {
                $Ids[] = $item['id'];
            }
        }
        $Ids[] = intval($parent_id);

        return [
            'form' => $form->createView(),
            'Parent' => $Parent,
            'Ids' => $Ids,
            'Categories' => $Categories,
            'TopCategories' => $TopCategories,
            'TargetCategory' => $TargetCategory,
            'forms' => $formViews,
        ];
    }
    ////////////////////////
    /**
     * @Route("/%eccube_admin_route%/product/category/image/add", name="admin_product_category_image_add")
     */
    public function imageAdd(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $filename = null;

        $files = $request->files->all();
        foreach ($files as $images) {
            if (isset($images['category_file'])) {
                $image = $images['category_file'];

                //ファイルフォーマット検証
                $mimeType = $image->getMimeType();
                if (0 !== strpos($mimeType, 'image')) {
                    throw new UnsupportedMediaTypeHttpException();
                }

                // 拡張子
                $extension = $image->getClientOriginalExtension();
                if (!in_array(strtolower($extension), $allowExtensions)) {
                    throw new UnsupportedMediaTypeHttpException();
                }

                $filename = date('mdHis') . uniqid('_') . '.' . $extension;
                $image->move($this->getParameter('eccube_temp_image_dir'), $filename);
            }
        }

        return $this->json(['filename' => $filename], 200);
    }
}
