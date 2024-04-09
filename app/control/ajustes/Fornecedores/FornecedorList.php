<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class FornecedorList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $formgrid;
    private $deleteButton;

    use Adianti\base\AdiantiStandardListTrait;
    
    public function __construct(){

        parent::__construct();


        //Conexão com a tabela
        $this->setDatabase('sample');
        $this->setActiveRecord('Fornecedor');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);

        $this->addFilterField('nome', 'like', 'nome');
        $this->addFilterField('nu_documento', 'like', 'nu_documento');

        //Criação do formulario 
        $this->form = new BootstrapFormBuilder('form_search_Fornecedor');
        $this->form->setFormTitle('Fornecedor');

        //Criação de fields
        $doc = new TEntry('nu_documento');
        $nome = new TEntry('nome');

        $this->form->addFields( [new TLabel('Nome')], [ $nome ] );
        $this->form->addFields( [new TLabel('CPF/CNPJ')], [ $doc ]  );

        //Tamanho dos fields
        $doc->setSize('100%');
        $nome->setSize('100%');

        $this->form->setData( TSession::getValue( __CLASS__.'_filter_data') );

        //Adicionar field de busca
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['FornecedorForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green'  );

        //Criando a data grid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        //Criando colunas da datagrid
        $column_id = new TDataGridColumn('id', 'Codigo', 'left');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left', );
        $column_doc = new TDataGridColumn('nu_documento', 'CPF/CNPJ', 'left');
        $column_email = new TDataGridColumn('email', 'Email', 'left');


        //add coluna da datagrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_doc);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_email);

        //Criando ações para o datagrid
        $column_id->setAction(new TAction([$this, 'onReload']), ['order'=> 'id']);
        $column_doc->setAction(new TAction([$this, 'onReload']), ['order'=> 'nu_documento']);
        $column_nome->setAction(new TAction([$this, 'onReload']), ['order'=> 'nome']);

        $action1 = new TDataGridAction(['FornecedorForm', 'onEdit'], ['id'=> '{id}', 'register_state' => 'false']);
        $action2 = new TDataGridAction([ $this, 'onDelete'], ['id'=> '{id}']);

        //Adicionando a ação na tela
        $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue' );
        $this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red' );


        //Criar datagrid 
        $this->datagrid->createModel();

        //Criação de paginador
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

      

        //Enviar para tela
        $panel = new TPanelGroup('', 'white');
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

          //Exportar
          $drodown = new TDropDown('Exportar', 'fa:list');
          $drodown->setPullSide('right');
          $drodown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
          $drodown->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table green');
          $drodown->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF'], ['register_state' => 'false',  'static'=>'1']), 'fa:file-pdf red');
          $panel->addHeaderWidget( $drodown);

        //Vertical container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
    
        parent::add($container);

       

    }
    public function onDelete($param)
  {
    if (isset($param['key'])) {
      // Obtém o ID do cliente a ser excluído
      $id = $param['key']; 

      TTransaction::open('sample');
      $entrada = Entrada::where('fornecedor_id', '=', $id)
                         ->first();
      if ($entrada) {
        $retorno_id =  $entrada->id;

        // Verifica se existem saídas relacionadas a este estoque
        if ($this->hasRelatedOutbound($retorno_id)) {
          new TMessage('error', 'Não é possível excluir este Fornecedor, pois existem vinculações.');
        } else {
          try {
            // Exclua o fornecedor
            TTransaction::open('sample');
            $object = new Fornecedor($id);
            $object->delete();

            TTransaction::close();

            // Recarregue a listagem
            $this->onReload();
            new TMessage('info', 'Registro excluído com sucesso.');
          } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
          }
        }
      } else {
        try {
          // Exclua o cliente
          TTransaction::open('sample');
          $object = new Fornecedor($id);
          $object->delete();

          TTransaction::close();

          // Recarregue a listagem
          $this->onReload();
          new TMessage('info', 'Registro excluído com sucesso.');
        } catch (Exception $e) {
          new TMessage('error', $e->getMessage());
        }
      }
    }
  }

  
  private function hasRelatedOutbound($id)
  {
    try {
      // Verifique se há entrada relacionadas a este estoque
      TTransaction::open('sample');
      $criteria = new TCriteria;
      $criteria->add(new TFilter('id', '=', $id));
      $repository = new TRepository('Entrada');
      $count = $repository->count($criteria);
      TTransaction::close();

      return $count > 0;
    } catch (Exception $e) {
      // Em caso de erro, trate-o de acordo com suas necessidades
      new TMessage('error', $e->getMessage());
      return false;
    }
  }
  
}