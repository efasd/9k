@if($customFields)
    <h5 class="col-12 pb-4">{!! trans('lang.main_fields') !!}</h5>
@endif
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">

    <div class="form-group row">
        <div class="col-6">
            <div class="form-text text-muted">Төлөв сонгох</div>
            {!! Form::select('category_id', $category, 'category', ['class' => 'select2 form-control']) !!}
        </div>
    </div>

    <div class="form-group row " id="productsHtml"></div>

</div>
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">

    <!-- Employee list -->
    <div class="form-group row ">
        <div class="col-6">
            <div class="form-text text-muted">Ажилтан сонгох</div>
            {!! Form::select('name', $employees, null, ['class' => 'select2 form-control']) !!}
        </div>
        <div class="col-6">
            <div class="form-text text-muted">Төлөв сонгох</div>
            {!! Form::select('order_status_id', $orderStatus, null, ['class' => 'select2 form-control']) !!}
        </div>
    </div>

</div>
@if($customFields)
    <div class="clearfix"></div>
    <div class="col-12 custom-field-container">
        <h5 class="col-12 pb-4">{!! trans('lang.custom_field_plural') !!}</h5>
        {!! $customFields !!}
    </div>
@endif
<!-- Submit Field -->
<div class="form-group col-12 text-right">
    <button type="submit" class="btn btn-{{setting('theme_color')}}"><i class="fa fa-save"></i> {{trans('lang.save')}} {{trans('lang.order')}}</button>
    <a href="{!! route('orders.index') !!}" class="btn btn-default"><i class="fa fa-undo"></i> {{trans('lang.cancel')}}</a>
</div>

@prepend('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.form-control[name="name"]').on('change', showSelectedValue);
            function showSelectedValue(event) {
                var target = $(event.target);
                const url = 'http://127.0.0.1:8000/calendar/product';
                $.ajax({
                    url: url,
                    dataType: 'json',
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        employeeName : target.find('option:selected').text()
                    },
                    success: function (res, status) {
                        if(status === 'success'){
                            let html = '';
                            res.forEach(product => {
                                html += "<div class='col-4' onclick='productSelect() ' ><div class='card' style='width: 10rem;'>"+
                                "<div class='card-body'><span class='card-title'></span>"+
                                "<p class='card-text'> нэр :" + product.name + "</p>"+
                                "<p class='card-text'> үнэ : " + product.price + "</p>"+
                                "</div></div></div>";
                            });
                            $('#productsHtml').html(html);
                        }
                    },
                    error : function(data, status, error){
                    },
                    complete: function (data, status) {
                    }
                });
            }

            function productSelect() {
                console.log('product');
            }
        });
    </script>
@endprepend

