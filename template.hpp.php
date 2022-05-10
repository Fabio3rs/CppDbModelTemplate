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
    [[nodiscard]] auto empty() const -> bool { return id == 0; }

<?= declare_findable($FIELDS, $FINDABLES) ?>

    auto findByID(uint64_t fid) -> bool;
    void save();

    [[nodiscard]] auto dump_json() const -> Poco::JSON::Object::Ptr;
    void from_json(const Poco::JSON::Object::Ptr &json);
    void from_json(const Poco::JSON::Object &json);

    void set_connection(std::shared_ptr<GenericDBConnection> conn) { usingconn = std::move(conn); }

    explicit <?= $MODEL_NAME ?>() = default;
    explicit <?= $MODEL_NAME ?>(const Poco::Dynamic::Var &var) {
        if (var.type() == typeid(Poco::JSON::Object))
        {
        from_json(var.extract<Poco::JSON::Object>());
        }else if (var.type() == typeid(Poco::JSON::Object::Ptr))
        {
        from_json(var.extract<Poco::JSON::Object::Ptr>());
        }else
        {
            std::string exceptiontext;
            // NOLINTNEXTLINE(readability-magic-numbers)
            exceptiontext.reserve(80);
            exceptiontext += "Type (";
            exceptiontext += var.type().name();
            exceptiontext += ") not compatible with <?= $MODEL_NAME ?>";
          throw Poco::BadCastException(exceptiontext);
        }
    }
    explicit <?= $MODEL_NAME ?>(std::shared_ptr<GenericDBConnection> conn)
        : usingconn(std::move(conn)) {}
};


template <>
inline auto
Poco::JSON::Object::getValue<<?= $MODEL_NAME ?>>(const std::string &key) -> <?= $MODEL_NAME ?> {
    Dynamic::Var value = get(key);
    return <?= $MODEL_NAME ?>(value);
}

template <>
class Poco::Dynamic::VarHolderImpl<<?= $MODEL_NAME ?>>
    : public VarHolderImpl<JSON::Object> {
  public:
    explicit VarHolderImpl(const <?= $MODEL_NAME ?> &val);
    VarHolderImpl(const VarHolderImpl &) = default;

    VarHolderImpl(VarHolderImpl &&) = default;

    auto operator=(const VarHolderImpl &) -> VarHolderImpl & = default;            // copy assignment operator
    auto operator=(VarHolderImpl &&) -> VarHolderImpl & = default;                 // move assignment operator
    ~VarHolderImpl() override;
};



