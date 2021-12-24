#pragma once

#include <Poco/JSON/JSON.h>
#include <Poco/Dynamic/Var.h>
#include <Poco/JSON/Object.h>

#include "../Database/GenericDBConnection.hpp"
#include <chrono>
#include <memory>
#include <optional>
#include <string>

class <?= $MODEL_NAME ?> {
    std::shared_ptr<GenericDBConnection> usingconn;

    auto update() -> int;
    void create();

public:
    /* Model fields */
    uint64_t id{0};
<?php
    foreach ($FIELDS as $k => $v)
    {
        echo '    ', nullablefieldtype($v), ' ', $v->name, extra_attribute_field($v), ';', "\n";
    }
    ?>

    /* Functions */
    auto empty() const -> bool { return id == 0; }

<?= declare_findable($FIELDS, $FINDABLES) ?>

    auto findByID(uint64_t fid) -> bool;
    void save();

    auto dump_json() const -> Poco::JSON::Object::Ptr;
    void from_json(const Poco::JSON::Object::Ptr &json);
    void from_json(const Poco::JSON::Object &json);

    explicit <?= $MODEL_NAME ?>() = default;
    explicit <?= $MODEL_NAME ?>(const Poco::Dynamic::Var &var) {
        Poco::JSON::Object::Ptr optr(
                new Poco::JSON::Object(var.extract<Poco::JSON::Object>()));
            from_json(optr);
    }
    explicit <?= $MODEL_NAME ?>(std::shared_ptr<GenericDBConnection> conn)
        : usingconn(conn) {}
};


template <>
inline <?= $MODEL_NAME ?> 
Poco::JSON::Object::getValue<<?= $MODEL_NAME ?>>(const std::string &key) const {
    Dynamic::Var value = get(key);
    return <?= $MODEL_NAME ?>(value);
}

template <>
class Poco::Dynamic::VarHolderImpl<<?= $MODEL_NAME ?>>
    : public VarHolderImpl<JSON::Object> {
  public:
    VarHolderImpl(const <?= $MODEL_NAME ?> &val);
    ~VarHolderImpl();
};



