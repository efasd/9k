@if($customFields)
<h5 class="col-12 pb-4">{!! trans('lang.main_fields') !!}</h5>
@endif
<div style="flex: 50%;max-width: 50%;padding: 0 4px;" class="column">
    <!-- User Id Field -->
    <div class="form-group row ">
        {!! Form::label('user_id', trans("lang.order_user_id"),['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::select('user_id', $user, null, ['class' => 'select2 form-control']) !!}
            <div class="form-text text-muted">{{ trans("lang.order_user_id_help") }}</div>
        </div>
    </div>

    <div class="form-group row ">
        {!! Form::label('employees[]', trans("lang.market_employees"),['class' => 'col-3 control-label text-right employees']) !!}
        <div class="col-9">
            {!! Form::select('employees[] ', $employees, null, ['class' => 'select2 form-control' ]) !!}
            <div class="form-text text-muted">{{ trans("lang.market_employees") }}</div>
        </div>
    </div>

    <div class="form-group row ">
        {!! Form::label('products[]', trans("lang.coupon_product_id"),['class' => 'col-3 control-label text-right products']) !!}
        <div class="col-9">
            {!! Form::select('products[]', $product, $productsSelected, ['class' => 'select2 form-control productSelected', 'multiple'=>'multiple']) !!}
            <div class="form-text text-muted">{{ trans("lang.coupon_product_id_help") }}</div>
        </div>
    </div>

    <div class="form-group row ">
        {!! Form::label('hint', trans("lang.select_day"), ['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::date('hint', 'null', ['class' => 'form-control','placeholder'=> trans("lang.select_day"), 'hint']) !!}
            <div class="form-text text-muted">
                {{ trans("lang.select_day") }}
            </div>
        </div>
    </div>

    <!-- Order Status Id Field -->
    <div class="form-group row ">
        {!! Form::label('order_status_id', trans("lang.order_order_status_id"),['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::select('order_status_id', $orderStatus, null, ['class' => 'select2 form-control order_status']) !!}
            <div class="form-text text-muted">{{ trans("lang.order_order_status_id_help") }}</div>
        </div>
    </div>

    <!-- Order Status Id Field -->
    <div class="container-fluid">

        <div class="row" id="appointmentData">

        </div>
    </div>

    <!-- Order Status Id Field -->
    <div class="form-group row ">
        {!! Form::label('appointment', trans("lang.order_order_status_id"),['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::select('appointment', $appointment, null, ['class' => 'select2 form-control']) !!}
            <div class="form-text text-muted">{{ trans("lang.order_order_status_id_help") }}</div>
        </div>
    </div>
    <!-- Status Field -->
    <div class="form-group row ">
        {!! Form::label('status', trans("lang.payment_status"),['class' => 'col-3 control-label text-right']) !!}
        <div class="col-9">
            {!! Form::select('status',
                [
                    'Waiting for Client' => trans('lang.order_pending'),
                    'Not Paid' => trans('lang.order_not_paid'),
                    'Paid' => trans('lang.order_paid'),
                ],
                isset($order->payment) ? $order->payment->status : '',
                ['class' => 'select2 form-control'])
            !!}
            <div class="form-text text-muted">{{ trans("lang.payment_status_help") }}</div>
        </div>
    </div>
    <!-- 'Boolean active Field' -->
    <div class="form-group row ">
        {!! Form::label('active', trans("lang.order_active"),['class' => 'col-3 control-label text-right']) !!}
        <div class="checkbox icheck">
            <label class="col-9 ml-2 form-check-inline">
                {!! Form::hidden('active', 0) !!}
                {!! Form::checkbox('active', 1, null) !!}
            </label>
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
    <button type="button" id="orderSubmit" class="btn btn-{{setting('theme_color')}}">
        Хадгалах
    </button>
    <a href="{!! route('orders.index') !!}" class="btn btn-default">
        <i class="fa fa-undo"></i> {{trans('lang.cancel')}}
    </a>
</div>


@prepend('scripts')
    <script type="text/javascript">
        var var15671147011688676454ble = '';

        const appData = {
            "date" : $("#hint").val(),
            "employeeId" : $(".employees").val(),
            "marketId" : $(".products").val(),
            "productId" : $(".products").val()
        };

        $.ajax({
            type: "POST",
            contentType: "application/json",
            dataType: "json",
            url: "{!!url('api/employee/appointment/getAppointment')!!}",
            data: JSON.stringify({
                "date":"2021-06-02",
                "employeeId":"111",
                "marketId" : "12",
                "productId": 60
            }),
            success: function(res) {
                const appointmentData = res.message;
                if (appointmentData && appointmentData.length > 0) {
                    appointmentData.forEach(appData => {
                        $("#appointmentData").append(
                            "<div class='col-lg-3'>" +
                                "<input type='radio' name='appointment' value="+ appData.id +">" +
                                "<label for='male'>" + appData.start_date +" - " + appData.end_date + "</label><br>" +
                            "</div>"
                        );
                    });
                }
            }, error: function(err) {
                console.log(err);
            }
        });

        var dz_var15671147011688676454ble = $(".productSelected").dropzone({

            url: "{!!url('employee/appointment/getAppointment')!!}",
            addRemoveLinks: true,
            init: function () {
                @if(isset($market) && $market->hasMedia('image'))
                dzInit(this, var15671147011688676454ble, '{!! url($market->getFirstMediaUrl('image','thumb')) !!}')
                @endif
            },
            accept: function (file, done) {
                dzAccept(file, done, this.element, "{!!config('medialibrary.icons_folder')!!}");
            },
            sending: function (file, xhr, formData) {
                dzSending(this, file, formData, '{!! csrf_token() !!}');
            },
            maxfilesexceeded: function (file) {
                dz_var15671147011688676454ble[0].mockFile = '';
                dzMaxfile(this, file);
            },
            complete: function (file) {
                dzComplete(this, file, var15671147011688676454ble, dz_var15671147011688676454ble[0].mockFile);
                dz_var15671147011688676454ble[0].mockFile = file;
            },
            removedfile: function (file) {
                dzRemoveFile(
                    file, var15671147011688676454ble, '{!! url("markets/remove-media") !!}',
                    'image', '{!! isset($market) ? $market->id : 0 !!}', '{!! url("uplaods/clear") !!}', '{!! csrf_token() !!}'
                );
            }
        });
        dz_var15671147011688676454ble[0].mockFile = var15671147011688676454ble;
        dropzoneFields['image'] = dz_var15671147011688676454ble;

        $("#orderSubmit").click(function() {
            const collectObject = {
                "user_id": $("#user_id").val(),
                "order_status_id": $("#order_status_id").val(),
                "tax": '0.0',
                "hint": $("appointment").val(),
                "employee_appointment_during": $("appointment").val(),
                "delivery_fee": '0.0',
                "coupon_id": '1111',
                "coupon_name": "coupon_name",
                "coupon_price": "coupon_price",
                "products": $(".products").val()
            };
            console.log('start');
            console.log(collectObject);
            console.log($("#user_id").val());
            console.log($("#hint").val());
            console.log($("#order_status_id").val());
            console.log($("#appointment").val());
            console.log($("#status").val());
            console.log($("#active").val());
            console.log($(".employees").val());
            console.log($(".products").val());
        });
    </script>
@endprepend
