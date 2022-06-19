import React from "react";
import '../../css/app.css';
import axios from "../components/axios";

class Home extends React.Component
{
    constructor(props) {
        super(props);
        this.reset();
    };

    reset() {
        this.state = {
            disabled: false,
            inputValue: '',
            forceValue: 0,
        };
    };

    GET = async() => {
        this.setState( {disabled: true} );
        try 
        {
            const Data = await axios.get("/api/screenshot?force=" + this.state.forceValue + "&url=" + this.state.inputValue); 
            console.log(Data.data);
            let ret = "("+Data.data.code+") "+Data.data.msg;
            if ("0" == Data.data.code) {
                ret += "\nwebsite=" + Data.data.data.url;
                ret += "\ntitle=" + Data.data.data.title;
                ret += "\ndescription=" + Data.data.data.description;
                let body = Data.data.data.body;
                ret += "\nbody length=" + body.length;
            }
            alert(ret);
        } 
        catch (error) 
        {
            console.log(error);
            alert("Error!"); 
        }
        this.setState( {disabled: false} );
    };

    updateInputValue(evt) {
        const val = evt.target.value;
        this.setState({
          inputValue: val
        });
    };

    updateCheckValue(evt) {
        let isChecked = evt.target.checked;
        this.setState({
            forceValue: (isChecked) ? 1 : 0
        });
    };
    
    render()
    {
        return (
            <div className="App">
                <header className="App-header">
                    <div className="box">
                        <input
                            name="firstName"
                            maxLength="256"
                            size="64"
                            disabled = {(this.state.disabled)? "disabled" : ""}
                            value={this.state.inputValue}
                            onChange={evt => this.updateInputValue(evt)}
                        />
                        <label
                            disabled = {(this.state.disabled)? "disabled" : ""}
                            >
                            <input 
                                type="checkbox"
                                disabled = {(this.state.disabled)? "disabled" : ""}
                                onChange={evt => this.updateCheckValue(evt)}
                            />
                            froce crawl to get the newest result
                        </label>
                    </div>
                    <div>
                        <button
                            onClick={this.GET}
                            disabled = {(this.state.disabled)? "disabled" : ""}
                            >
                            try to crawl
                        </button>
                    </div>
                </header>
            </div>
        );
    };
};

export default Home;