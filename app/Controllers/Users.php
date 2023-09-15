<?php

namespace App\Controllers;

use App\Models\EstadoModel;
use App\Models\PaisModel;
use App\Models\UserModel;
use App\Models\CidadeModel;
use App\Models\TokenModel;
use App\Models\LogAcesso;
use DateTime;
use Exception;

class Users extends BaseController
{
    public function index()
    {
        helper(['form']);
        $data = [
            'title' => 'Login',
        ];

        if ($this->request->getMethod() == 'post') {
            //VALIDAÇÕES
            $rules = [
                'email' => 'required|min_length[6]|max_length[100]|valid_email',
                'senha' => 'required|min_length[8]|max_length[255]|validateUser[email,senha]',
            ];

            $errors = [
                'senha' => [
                    'validateUser' => 'e-mail ou senha não conferem'
                ]
            ];


            if (!$this->validate($rules, $errors)) {
                $data['validation'] = $this->validator;
            } else {
                $model =  new UserModel();
                $user = $model->where('email', $this->request->getVar('email'))->first();
                $this->setUserSession($user);
                $acesso =  new LogAcesso();
                $data = [
                    'idUser' => $user['id'],
                ];

                $acesso->save($data);
                return redirect()->to('inicio');
            }
        }        
        echo view('login', $data);
        
    }


    private function setUserSession($user)
    {
        $model =  new UserModel();
        $data = [
            'id' => $user['id'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'email' => $user['email'],
            'type' => $user['type'],
            'estado' => $user['estado'],
            'isLoggedIn' => true,
            'data' => $model->findAll(),
        ];

        session()->set($data);
    }   

    public function register()
    {
        $paisModel = new PaisModel();
        $paises = $paisModel->selectPaises();

        $estadoModel = new EstadoModel();
        $uf = $estadoModel->selectUF();

        $data = [
            'options_paises' => $paises,
            'options_uf' => $uf,

            'title' => 'Inscreva-se',
        ];

        helper(['form']);

        if ($this->request->getMethod() == 'post') {
            //VALIDAÇÕES
            $rules = [
                'nome' => 'required|min_length[3]|max_length[20]',
                'sobrenome' => 'required|min_length[3]|max_length[100]',
                'email' => 'required|min_length[6]|max_length[100]|valid_email|is_unique[users.email]',
                'senha' => 'required|min_length[8]|max_length[255]',
                'senha_confirmacao' => 'matches[senha]',
                'termos' => 'required',
            ];

            if (!$this->validate($rules)) {
                $data['validation'] = $this->validator;
            } else {
                //salva no BD
                $model =  new UserModel();

                $newData = [
                    'firstname' => $this->request->getVar('nome'),
                    'lastname' => $this->request->getVar('sobrenome'),
                    'email' => $this->request->getVar('email'),
                    'pais' => $this->request->getVar('paises'),
                    'estado' => $this->request->getVar('estados'),
                    'cidade' => $this->request->getVar('cidades'),
                    'type' => (int) $this->request->getVar('categoria'),
                    'uf' => $this->request->getVar('uf'),
                    'crf' => $this->request->getVar('crf'),
                    'telefone' => $this->request->getVar('telefone'),
                    'celular' => $this->request->getVar('celular'),
                    'cpf' => $this->request->getVar('cpf'),
                    'password' => $this->request->getVar('senha'),
                ];

                // var_dump($newData); exit;

                if ($model->save($newData)) {
                    if ($this->sendEmail($newData)) {
                        $session = session();
                        $session->setFlashdata('success', 'Sua inscrição foi recebida com sucesso!');
                        return redirect()->to(base_url());
                    } else {
                        echo "Erro ao enviar email";
                        exit;
                    }
                } else {
                    echo "Erro ao salvar";
                    exit;
                }
            }
        }

        echo view('templates/headerAcesso', $data);
        echo view('register', $data);
        echo view('templates/footer');
    }

    //--------------------------------------------------------------------

    public function recuperacaoSenha()
    {
        $data = [
            'title' => 'Recuperação de Senha',
        ];

        helper(['form']);


        if ($this->request->getMethod() == 'post') {
            $rules = [
                'email' => 'required|min_length[6]|max_length[100]|valid_email',
            ];

            if (!$this->validate($rules)) {
                $data['validation'] = $this->validator;
            } else {
                $email = $this->request->getVar('email');

                $model =  new UserModel();
                $user = $model->where('email', $email)->find();

                if ($user != NULL) {
                    if ($user[0] != NULL) {
                        if ($this->recuperarSenha($user[0])) {
                            $session = session();
                            $session->setFlashdata('success', 'Um e-mail de redefinição foi enviado!');
                            return redirect()->to(base_url());
                        } else {
                            echo "Erro ao enviar email";
                            exit;
                        }
                    } else {
                        $session = session();
                        $session->setFlashdata('danger', 'Esse email não está cadastrado em nossa base de dados!');
                        return redirect()->to(base_url('recuperacao'));
                        exit;
                    }
                } else {
                    $session = session();
                    $session->setFlashdata('danger', 'Esse email não está cadastrado em nossa base de dados!');
                    return redirect()->to(base_url('recuperacao'));
                    exit;
                }
            }
        }

        echo view('templates/headerAcesso', $data);
        echo view('recuperacao', $data);
        echo view('templates/footer');
    }

  

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url());
    }
}
