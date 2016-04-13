@extends('admin.layouts.layout')

@section('content') 
     
    
   @include('admin/shops/partials/_search')
   
	
	<div class="table-responsive">
        {!! link_to_route('store.admin.shops.create','Nueva Tienda',null,['class'=>'btn btn-success']) !!}

        <table class="table table-striped  ">
        <thead>
            <tr>

                <th>#</th>
                <th>Nombre</th>
                <th>Canton</th>
                <th>Detalles</th>
                <th>Cant. Productos.</th>
                <th>Creado</th>
                <th>Publicado</th>
                <th><i class="glyphicon glyphicon-cog"></i></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($shops as $shop)
                @if($shop->responsable_id == $currentUser->id || $currentUser->hasrole('administrator'))
                <tr>
                    <td>{!! $shop->id !!}</td>
                    <td>{!! link_to_route('store.admin.shops.edit', $shop->name, $shop->id) !!}</td>
                    <td>{!! $shop->canton !!}</td>
                    <td>{!! str_limit($shop->details, 20) !!}</td>
                    <td>{!! $shop->products->count() !!}</td>
                    <td>{!! $shop->created_at !!}</td>
                   
                    
                </tr>
                @endif
            @endforeach
        </tbody>
       <tfoot>
           
            @if ($shops)
                <td  colspan="10" class="pagination-container">{!!$shops->appends(['q' => $search,'published'=>$selectedStatus])->render()!!}</td>
                 @endif 
            
             
        </tfoot>
    </table>
     
    </div>  

{!! Form::open(array('method' => 'post', 'id' => 'form-pub-unpub')) !!}{!! Form::close() !!}
{!! Form::open(['method' => 'post', 'id' => 'form-feat-unfeat']) !!}{!! Form::close() !!}
{!! Form::open(['method' => 'delete', 'id' =>'form-delete','data-confirm' => 'Estas seguro?']) !!}{!! Form::close() !!}

@stop